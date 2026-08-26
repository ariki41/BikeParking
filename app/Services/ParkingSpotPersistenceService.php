<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\User;
use App\ValueObjects\PersistedParkingSpotImages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParkingSpotPersistenceService
{
    public function __construct(
        private readonly ParkingSpotImageService $images,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, User $createdBy): ParkingSpot
    {
        $postalcode = $this->postalcode($input['postalcode']);
        $persistedImages = null;

        try {
            $parkingSpot = DB::transaction(function () use ($input, $postalcode, $createdBy, &$persistedImages): ParkingSpot {
                $parkingSpot = new ParkingSpot;
                $parkingSpot->user_id = $createdBy->id;
                $this->fillParkingSpot($parkingSpot, $input, $postalcode);
                $parkingSpot->save();

                $persistedImages = $this->images->persistConfirmedImages(
                    $parkingSpot,
                    $this->confirmedImagePaths($input),
                );
                $parkingSpot->image_path = $persistedImages->paths[0] ?? null;
                $parkingSpot->save();
                $this->images->replaceParkingSpotImages($parkingSpot, $persistedImages->paths);
                $this->saveParkingSpotRates($parkingSpot, $input['rates']);

                return $parkingSpot;
            });
        } catch (\Throwable $exception) {
            if ($persistedImages instanceof PersistedParkingSpotImages) {
                $this->images->deleteImagePaths($persistedImages->createdPaths);
            }

            throw $exception;
        }

        $this->images->deleteImagePaths($persistedImages->temporaryPaths);

        return $parkingSpot;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input, User $updatedBy): ParkingSpot
    {
        $postalcode = $this->postalcode($input['postalcode']);
        $persistedImages = null;
        $removedImagePaths = [];

        try {
            $parkingSpot = DB::transaction(function () use ($input, $postalcode, $updatedBy, &$persistedImages, &$removedImagePaths): ParkingSpot {
                $parkingSpot = ParkingSpot::query()
                    ->lockForUpdate()
                    ->findOrFail($input['id']);
                $parkingSpot->load(['images', 'rates']);
                $originalImagePaths = $parkingSpot->image_paths;
                $originalRates = $this->normalizeStoredRates($parkingSpot);

                $this->fillParkingSpot($parkingSpot, $input, $postalcode);
                $persistedImages = $this->images->persistConfirmedImages(
                    $parkingSpot,
                    $this->confirmedImagePaths($input),
                );
                $parkingSpot->image_path = $persistedImages->paths[0] ?? null;

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
                $this->images->replaceParkingSpotImages($parkingSpot, $persistedImages->paths);

                if ($originalImagePaths !== $persistedImages->paths) {
                    $changes['images'] = [
                        'before' => $originalImagePaths,
                        'after' => $persistedImages->paths,
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

                $removedImagePaths = array_values(array_diff($originalImagePaths, $persistedImages->paths));

                return $parkingSpot;
            });
        } catch (\Throwable $exception) {
            if ($persistedImages instanceof PersistedParkingSpotImages) {
                $this->images->deleteImagePaths($persistedImages->createdPaths);
            }

            throw $exception;
        }

        $this->images->deleteImagePaths([
            ...$persistedImages->temporaryPaths,
            ...$removedImagePaths,
        ]);

        return $parkingSpot;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function fillParkingSpot(ParkingSpot $parkingSpot, array $input, Postalcode $postalcode): void
    {
        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode()->associate($postalcode);
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $this->normalizeDatabaseTime($input['opening_time']);
        $parkingSpot->closing_time = $this->normalizeDatabaseTime($input['closing_time']);
        $parkingSpot->capacity = $input['capacity'];
    }

    private function postalcode(string $postalcode): Postalcode
    {
        $postalcode = Postalcode::query()
            ->where('postalcode', str_replace('-', '', $postalcode))
            ->first();

        if ($postalcode === null) {
            throw ValidationException::withMessages([
                'postalcode' => '郵便番号に対応する住所が見つかりません。',
            ]);
        }

        return $postalcode;
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

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    private function confirmedImagePaths(array $input): array
    {
        if (isset($input['image_paths']) && is_array($input['image_paths'])) {
            return $input['image_paths'];
        }

        return array_values(array_filter([$input['image_path'] ?? null]));
    }
}
