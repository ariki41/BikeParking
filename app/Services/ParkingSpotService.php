<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ParkingSpotService
{
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
        $parkingSpot->opening_time = $input['opening_time'];
        $parkingSpot->closing_time = $input['closing_time'];
        $parkingSpot->capacity = $input['capacity'];

        $parkingSpot->save();

        $parkingSpot->image_path = $this->persistConfirmedImage($parkingSpot, $input['image_path'] ?? null);
        $parkingSpot->save();

        $this->saveParkingSpotRates($parkingSpot, $input['rates']);
    }

    public function updateParkingSpot($input)
    {
        $id = $input['id'];
        $parkingSpot = ParkingSpot::findOrFail($id);
        $originalImagePath = $parkingSpot->image_path;

        $postalcode = Postalcode::getPostalcodeId($input['postalcode'])->first()->id ?? null;
        if (! $postalcode) {
            return redirect()->route('parking_spot.edit', ['id' => $id])->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }

        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode = $postalcode;
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $input['opening_time'];
        $parkingSpot->closing_time = $input['closing_time'];
        $parkingSpot->capacity = $input['capacity'];
        $parkingSpot->image_path = $this->persistConfirmedImage($parkingSpot, $input['image_path'] ?? null);

        $parkingSpot->save();

        if ($originalImagePath && $originalImagePath !== $parkingSpot->image_path) {
            Storage::disk('public')->delete($originalImagePath);
        }

        $parkingSpot->rates()->delete();
        $this->saveParkingSpotRates($parkingSpot, $input['rates']);

        session()->forget('parking_spot_form');
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

    public function prepareImagePathForConfirm(Request $request, ?string $currentPath): ?string
    {
        if (! $request->hasFile('image')) {
            return $currentPath;
        }

        if ($this->isTemporaryImagePath($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        return $request->file('image')->store('temp/parking-spots', 'public');
    }

    private function persistConfirmedImage(ParkingSpot $parkingSpot, ?string $imagePath): ?string
    {
        if (blank($imagePath) || ! $this->isTemporaryImagePath($imagePath)) {
            return $imagePath;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($imagePath)) {
            return null;
        }

        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $timestamp = CarbonImmutable::now()->format('YmdHisv');
        $filename = $parkingSpot->id.'_'.$timestamp.($extension ? '.'.$extension : '');
        $permanentPath = 'parking-spots/'.$filename;

        $disk->copy($imagePath, $permanentPath);
        $disk->delete($imagePath);

        return $permanentPath;
    }

    private function isTemporaryImagePath(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'temp/parking-spots/');
    }
}
