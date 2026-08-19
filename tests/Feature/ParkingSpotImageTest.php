<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParkingSpotImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_edit_forms_allow_up_to_four_images(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $this->setImages($parkingSpot, [
            'parking-spots/edit-1.webp',
            'parking-spots/edit-2.webp',
        ]);

        $this->actingAs($user)
            ->get(route('parking_spot.create'))
            ->assertOk()
            ->assertSee('name="images[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('最大4枚');

        $this->actingAs($user)
            ->get(route('parking_spot.edit', $parkingSpot->id))
            ->assertOk()
            ->assertSee('name="images[]"', false)
            ->assertSee('/storage/parking-spots/edit-1.webp')
            ->assertSee('/storage/parking-spots/edit-2.webp')
            ->assertSee('現在の画像を選択した画像へすべて置き換えます。');
    }

    public function test_confirm_accepts_and_previews_four_images(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $this->fakeGeocode();

        $response = $this->actingAs($user)
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'images' => $this->fakeImages(4),
            ]));

        $response->assertOk()->assertSee('4枚');

        $imagePaths = session('create_parking_spot_form.image_paths');
        $this->assertCount(4, $imagePaths);
        $this->assertSame($imagePaths[0], session('create_parking_spot_form.image_path'));

        foreach ($imagePaths as $position => $imagePath) {
            $this->assertStringStartsWith('temp/parking-spots/', $imagePath);
            $this->assertStringEndsWith('.webp', $imagePath);
            Storage::disk('public')->assertExists($imagePath);
            $response->assertSee('/storage/'.$imagePath);
            $response->assertSee('駐輪場画像 '.($position + 1));
        }
    }

    public function test_confirm_rejects_five_images(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'images' => $this->fakeImages(5),
            ]))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['images']);
    }

    public function test_store_persists_four_ordered_images_and_home_uses_the_first_image(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $tempPaths = collect(range(1, 4))
            ->map(function (int $position): string {
                $path = "temp/parking-spots/confirmed-{$position}.webp";
                Storage::disk('public')->put($path, "image-{$position}");

                return $path;
            })
            ->all();

        $input = $this->confirmedInput($postalcode, [
            'name' => '複数画像登録テスト駐輪場',
            'image_paths' => $tempPaths,
            'image_path' => $tempPaths[0],
        ]);

        $this->actingAs($user)
            ->withSession(['create_parking_spot_form' => $input])
            ->post(route('parking_spot.store'))
            ->assertRedirect(route('home'));

        $parkingSpot = ParkingSpot::with('images')
            ->where('name', '複数画像登録テスト駐輪場')
            ->firstOrFail();
        $this->assertCount(4, $parkingSpot->images);
        $this->assertSame([0, 1, 2, 3], $parkingSpot->images->pluck('position')->all());
        $this->assertSame($parkingSpot->images[0]->path, $parkingSpot->image_path);

        foreach ($parkingSpot->images as $position => $image) {
            $suffix = $position === 0 ? '' : '_'.($position + 1);
            $this->assertMatchesRegularExpression(
                '/^parking-spots\/'.$parkingSpot->id.'_\d{17}'.preg_quote($suffix, '/').'\.webp$/',
                $image->path,
            );
            Storage::disk('public')->assertExists($image->path);
            Storage::disk('public')->assertMissing($tempPaths[$position]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/storage/'.$parkingSpot->images[0]->path)
            ->assertDontSee('/storage/'.$parkingSpot->images[1]->path);
    }

    public function test_edit_confirmation_and_update_replace_all_images(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $originalPaths = [
            'parking-spots/original-1.webp',
            'parking-spots/original-2.webp',
        ];
        foreach ($originalPaths as $path) {
            Storage::disk('public')->put($path, 'original');
        }
        $this->setImages($parkingSpot, $originalPaths);
        $this->fakeGeocode();

        $confirmResponse = $this->actingAs($user)
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'id' => $parkingSpot->id,
                'image_paths' => $originalPaths,
                'images' => $this->fakeImages(3),
            ]));

        $confirmResponse->assertOk()->assertSee('3枚');
        $tempPaths = session('edit_parking_spot_form.image_paths');
        $this->assertCount(3, $tempPaths);

        $this->actingAs($user)
            ->post(route('parking_spot.update'))
            ->assertRedirect(route('home'));

        $parkingSpot->refresh()->load('images');
        $this->assertCount(3, $parkingSpot->images);
        $this->assertSame($parkingSpot->images[0]->path, $parkingSpot->image_path);

        foreach ($originalPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        foreach ($parkingSpot->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }

        $history = ParkingSpotUpdateHistory::sole();
        $this->assertSame($originalPaths, $history->changes['images']['before']);
        $this->assertSame($parkingSpot->images->pluck('path')->all(), $history->changes['images']['after']);
    }

    public function test_detail_displays_all_images_and_legacy_records_keep_their_fallbacks(): void
    {
        [$parkingSpot] = $this->createParkingSpot();
        $imagePaths = [
            'parking-spots/gallery-1.webp',
            'parking-spots/gallery-2.webp',
            'parking-spots/gallery-3.webp',
        ];
        $this->setImages($parkingSpot, $imagePaths);

        $detailResponse = $this->get(route('parking_spot.show', $parkingSpot->id))->assertOk();
        foreach ($imagePaths as $position => $path) {
            $detailResponse
                ->assertSee('/storage/'.$path)
                ->assertSee('画像'.($position + 1).'を表示');
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/storage/'.$imagePaths[0])
            ->assertDontSee('/storage/'.$imagePaths[1]);

        $parkingSpot->images()->delete();
        $parkingSpot->forceFill(['image_path' => 'parking-spots/legacy.webp'])->save();
        $this->get(route('parking_spot.show', $parkingSpot->id))
            ->assertOk()
            ->assertSee('/storage/parking-spots/legacy.webp');

        $parkingSpot->forceFill(['image_path' => null])->save();
        $this->get(route('parking_spot.show', $parkingSpot->id))
            ->assertOk()
            ->assertSee('/images/noimage.jpg');
    }

    private function createParkingSpot(): array
    {
        $prefecture = Prefecture::create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $city = City::create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);
        $postalcode = Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
        $user = User::create([
            'user_id' => 'image-user',
            'name' => 'Image User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '画像テスト駐輪場',
            'postalcode' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user, $postalcode];
    }

    private function setImages(ParkingSpot $parkingSpot, array $paths): void
    {
        $parkingSpot->images()->delete();
        $parkingSpot->images()->createMany(
            collect($paths)
                ->map(fn (string $path, int $position) => compact('path', 'position'))
                ->all(),
        );
        $parkingSpot->forceFill(['image_path' => $paths[0] ?? null])->save();
    }

    private function fakeImages(int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $position) => UploadedFile::fake()->image("parking-spot-{$position}.jpg"))
            ->all();
    }

    private function fakeGeocode(): void
    {
        Http::fake([
            '*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都千代田区千代田1-2'],
                ]],
            ]),
        ]);
    }

    private function validFormInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => '複数画像フォームテスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->validRateInput()],
        ], $overrides);
    }

    private function confirmedInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => '複数画像保存テスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->validRateInput()],
        ], $overrides);
    }

    private function validRateInput(): array
    {
        return [
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ];
    }
}
