<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ParkingSpotService
{
    private const MAX_FINAL_IMAGE_BYTES = 5 * 1024 * 1024;

    public function saveParkingSpot($input)
    {
        $postalcodeId = Postalcode::getPostalcodeId($input['postalcode'])->first()->id ?? null;
        if (! $postalcodeId) {
            return redirect()->route('parking_spot.create')->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }

        $parkingSpot = new ParkingSpot;
        $parkingSpot->user_id = auth()->id();
        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode = $postalcodeId;
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $this->normalizeDatabaseTime($input['opening_time']);
        $parkingSpot->closing_time = $this->normalizeDatabaseTime($input['closing_time']);
        $parkingSpot->capacity = $input['capacity'];

        $parkingSpot->save();

        $imagePaths = $this->persistConfirmedImages($parkingSpot, $this->confirmedImagePaths($input));
        $parkingSpot->image_path = $imagePaths[0] ?? null;
        $parkingSpot->save();
        $this->replaceParkingSpotImages($parkingSpot, $imagePaths);

        $this->saveParkingSpotRates($parkingSpot, $input['rates']);
    }

    public function updateParkingSpot($input, ?User $updatedBy = null)
    {
        $updatedBy ??= auth()->user();

        if (! $updatedBy instanceof User) {
            throw new \LogicException('駐輪場の更新履歴には更新ユーザーが必要です。');
        }

        $id = $input['id'];
        $parkingSpot = ParkingSpot::with(['images', 'rates'])->findOrFail($id);
        $originalImagePaths = $parkingSpot->image_paths;
        $originalRates = $this->normalizeStoredRates($parkingSpot);

        $postalcode = Postalcode::getPostalcodeId($input['postalcode'])->first()->id ?? null;
        if (! $postalcode) {
            return redirect()->route('parking_spot.edit', ['id' => $id])->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }

        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode = $postalcode;
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $this->normalizeDatabaseTime($input['opening_time']);
        $parkingSpot->closing_time = $this->normalizeDatabaseTime($input['closing_time']);
        $parkingSpot->capacity = $input['capacity'];
        $imagePaths = $this->persistConfirmedImages($parkingSpot, $this->confirmedImagePaths($input));
        $parkingSpot->image_path = $imagePaths[0] ?? null;

        $changes = collect($parkingSpot->getDirty())
            ->except(['image_path', 'updated_at'])
            ->mapWithKeys(fn ($after, string $field) => [
                $field => [
                    'before' => $parkingSpot->getOriginal($field),
                    'after' => $after,
                ],
            ])
            ->all();

        $parkingSpot->save();
        $this->replaceParkingSpotImages($parkingSpot, $imagePaths);

        $removedImagePaths = array_diff($originalImagePaths, $imagePaths);
        if ($removedImagePaths !== []) {
            Storage::disk('public')->delete($removedImagePaths);
        }

        if ($originalImagePaths !== $imagePaths) {
            $changes['images'] = [
                'before' => $originalImagePaths,
                'after' => $imagePaths,
            ];
        }

        $parkingSpot->rates()->delete();
        $this->saveParkingSpotRates($parkingSpot, $input['rates']);

        $updatedRates = $this->normalizeInputRates($input['rates']);
        if ($originalRates !== $updatedRates) {
            $changes['rates'] = [
                'before' => $originalRates,
                'after' => $updatedRates,
            ];
        }

        ParkingSpotUpdateHistory::create([
            'parking_spot_id' => $parkingSpot->id,
            'user_id' => $updatedBy->id,
            'changes' => $changes,
        ]);

        session()->forget('parking_spot_form');

        return $parkingSpot;
    }

    private function normalizeStoredRates(ParkingSpot $parkingSpot): array
    {
        return $parkingSpot->rates
            ->map(fn (ParkingSpotRates $rate) => [
                'day_type' => $rate->day_type,
                'start_time' => substr((string) $rate->start_time, 0, 5),
                'end_time' => substr((string) $rate->end_time, 0, 5),
                'unit_minutes' => (int) $rate->unit_minutes,
                'rate' => (int) $rate->rate,
                'free_minutes' => (int) $rate->free_minutes,
                'max_rate' => $rate->max_rate === null ? null : (int) $rate->max_rate,
            ])
            ->values()
            ->all();
    }

    private function normalizeInputRates(array $rates): array
    {
        return collect($rates)
            ->map(fn (array $rate) => [
                'day_type' => $rate['day_type'],
                'start_time' => substr($rate['start_time'], 0, 5),
                'end_time' => substr($rate['end_time'], 0, 5),
                'unit_minutes' => (int) $rate['unit_minutes'],
                'rate' => (int) $rate['rate'],
                'free_minutes' => (int) ($rate['free_minutes'] ?? 0),
                'max_rate' => ($rate['no_max_rate'] ?? false)
                    ? null
                    : (isset($rate['max_rate']) ? (int) $rate['max_rate'] : null),
            ])
            ->values()
            ->all();
    }

    private function normalizeDatabaseTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function saveParkingSpotRates(ParkingSpot $parkingSpot, array $rates): void
    {
        foreach ($rates as $rate) {
            ParkingSpotRates::create([
                'parking_spot_id' => $parkingSpot->id,
                'day_type' => $rate['day_type'],
                'start_time' => $rate['start_time'],
                'end_time' => $rate['end_time'],
                'unit_minutes' => $rate['unit_minutes'],
                'rate' => $rate['rate'],
                'free_minutes' => $rate['free_minutes'] ?? 0,
                'max_rate' => ($rate['no_max_rate'] ?? false) ? null : ($rate['max_rate'] ?? null),
            ]);
        }
    }

    public function getYolpLonLat($address)
    {
        try {
            $responce = Http::timeout(5)
                ->retry(3, 100)
                ->get(env('YOLP_GEOCODE_URL'), [
                    'appid' => env('YOLP_CLIENT_ID'),
                    'query' => $address,
                    'sort' => 'score',
                    'results' => 1,
                    'output' => 'json',
                ])
                ->throw();
            $yolp = $responce->json();
        } catch (RequestException $e) {
            throw new RequestException($e->response, 'YOLP API Error');
        }

        if (isset($yolp['Feature'][0])) {
            [$lon, $lat] = explode(',', $yolp['Feature'][0]['Geometry']['Coordinates']);
            $address = $yolp['Feature'][0]['Property']['Address'];
            $yolpLocation = ['lon' => $lon, 'lat' => $lat, 'address' => $address];

            return $yolpLocation;
        }
    }

    public function prepareImagePathsForConfirm(Request $request, array $currentPaths, array $allowedPermanentPaths = []): array
    {
        $currentPaths = $this->normalizeImagePaths($currentPaths);
        $uploadedImages = $request->file('images', []);
        $uploadedImages = is_array($uploadedImages) ? array_values($uploadedImages) : [];

        if ($request->hasFile('image')) {
            $uploadedImages[] = $request->file('image');
        }

        if ($uploadedImages === []) {
            foreach ($currentPaths as $path) {
                if (! $this->isTemporaryImagePath($path) && ! in_array($path, $allowedPermanentPaths, true)) {
                    throw ValidationException::withMessages([
                        'image_paths' => '保持している画像情報が正しくありません。',
                    ]);
                }
            }

            return $currentPaths;
        }

        $timestamp = CarbonImmutable::now()->format('YmdHisv');
        $tempPaths = [];

        try {
            foreach ($uploadedImages as $position => $uploadedImage) {
                $suffix = $position === 0 ? '' : '_'.($position + 1);
                $tempPath = 'temp/parking-spots/'.$timestamp.$suffix.'.webp';
                $webpBinary = $this->convertImageToWebpWithinLimit($uploadedImage->getRealPath());
                Storage::disk('public')->put($tempPath, $webpBinary);
                $tempPaths[] = $tempPath;
            }
        } catch (\Throwable) {
            Storage::disk('public')->delete($tempPaths);

            throw ValidationException::withMessages([
                'images' => '画像をWebP形式へ変換できませんでした。別の画像をお試しください。',
            ]);
        }

        Storage::disk('public')->delete(array_filter(
            $currentPaths,
            fn (string $path) => $this->isTemporaryImagePath($path),
        ));

        return $tempPaths;
    }

    private function persistConfirmedImages(ParkingSpot $parkingSpot, array $imagePaths): array
    {
        return collect($this->normalizeImagePaths($imagePaths))
            ->map(fn (string $imagePath, int $position) => $this->persistConfirmedImage($parkingSpot, $imagePath, $position))
            ->filter()
            ->values()
            ->all();
    }

    private function persistConfirmedImage(ParkingSpot $parkingSpot, string $imagePath, int $position): ?string
    {
        if (! $this->isTemporaryImagePath($imagePath)) {
            return $imagePath;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($imagePath)) {
            return null;
        }

        $timestamp = CarbonImmutable::now()->format('YmdHisv');
        $suffix = $position === 0 ? '' : '_'.($position + 1);
        $filename = $parkingSpot->id.'_'.$timestamp.$suffix.'.webp';
        $permanentPath = 'parking-spots/'.$filename;

        $disk->copy($imagePath, $permanentPath);
        $disk->delete($imagePath);

        return $permanentPath;
    }

    private function confirmedImagePaths(array $input): array
    {
        if (isset($input['image_paths']) && is_array($input['image_paths'])) {
            return $input['image_paths'];
        }

        return array_values(array_filter([$input['image_path'] ?? null]));
    }

    private function normalizeImagePaths(array $imagePaths): array
    {
        return collect($imagePaths)
            ->filter(fn ($path) => is_string($path) && filled($path))
            ->unique()
            ->values()
            ->all();
    }

    private function replaceParkingSpotImages(ParkingSpot $parkingSpot, array $imagePaths): void
    {
        $parkingSpot->images()->delete();

        $parkingSpot->images()->createMany(
            collect($imagePaths)
                ->map(fn (string $path, int $position) => compact('path', 'position'))
                ->all(),
        );

        $parkingSpot->unsetRelation('images');
    }

    private function isTemporaryImagePath(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'temp/parking-spots/');
    }

    private function convertImageToWebpWithinLimit(string $sourcePath): string
    {
        $manager = $this->imageManager();
        $quality = 85;
        $maxWidth = null;

        while (true) {
            $image = $manager->decodePath($sourcePath);

            if ($maxWidth !== null && $image->width() > $maxWidth) {
                $image = $image->scaleDown(width: $maxWidth);
            }

            $encoded = $image->encode(new WebpEncoder(quality: $quality));
            $binary = (string) $encoded;

            if (strlen($binary) <= self::MAX_FINAL_IMAGE_BYTES) {
                return $binary;
            }

            if ($quality > 55) {
                $quality -= 10;

                continue;
            }

            $nextWidth = $maxWidth
                ? (int) floor($maxWidth * 0.85)
                : (int) floor($image->width() * 0.85);

            if ($nextWidth < 600 || $nextWidth >= $image->width()) {
                break;
            }

            $maxWidth = $nextWidth;
        }

        throw new \RuntimeException('Unable to convert image within size limit.');
    }

    private function imageManager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return new ImageManager(new ImagickDriver);
        }

        return new ImageManager(new GdDriver);
    }
}
