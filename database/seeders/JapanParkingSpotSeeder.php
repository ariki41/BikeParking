<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class JapanParkingSpotSeeder extends Seeder
{
    /**
     * Add nationwide parking-spot samples without removing existing records.
     */
    public function run(): void
    {
        $parkingSpotCount = $this->positiveIntegerSetting('parking_spot.sample_data.parking_spot_count');
        $insertChunkSize = $this->positiveIntegerSetting('parking_spot.sample_data.insert_chunk_size');
        $userIds = User::query()->pluck('id')->all();

        if ($userIds === []) {
            throw new LogicException('JapanParkingSpotSeeder requires at least one user. Run DatabaseSeeder or create a user first.');
        }

        $locationsByPrefecture = $this->locationsByPrefecture($parkingSpotCount);
        $prefectureQueue = $this->prefectureQueue(array_keys($locationsByPrefecture), $parkingSpotCount);
        $requiredLocations = array_count_values($prefectureQueue);

        foreach ($requiredLocations as $prefecture => $requiredLocationCount) {
            if (count($locationsByPrefecture[$prefecture]) < $requiredLocationCount) {
                throw new LogicException("JapanParkingSpotSeeder requires at least {$requiredLocationCount} usable locations for {$prefecture}.");
            }

            shuffle($locationsByPrefecture[$prefecture]);
        }

        $now = now();
        $seedPrefix = '国内サンプル駐輪場 '.now()->format('YmdHis').'-'.Str::lower(Str::random(6));

        for ($offset = 0; $offset < $parkingSpotCount; $offset += $insertChunkSize) {
            $parkingSpots = [];
            $parkingSpotNames = [];

            for ($position = $offset; $position < min($offset + $insertChunkSize, $parkingSpotCount); $position++) {
                $prefecture = $prefectureQueue[$position];
                $location = array_pop($locationsByPrefecture[$prefecture]);

                if ($location === null) {
                    throw new LogicException("JapanParkingSpotSeeder ran out of locations for {$prefecture}.");
                }

                $name = sprintf(
                    '%s %s駐輪場-%05d',
                    $seedPrefix,
                    $location['city'],
                    $position + 1,
                );

                $parkingSpots[] = [
                    'user_id' => $userIds[array_rand($userIds)],
                    'name' => $name,
                    'postalcode_id' => $location['postalcode_id'],
                    'address' => $this->addressFor($location),
                    'longitude' => $this->randomizedCoordinate($location['longitude']),
                    'latitude' => $this->randomizedCoordinate($location['latitude']),
                    'capacity' => random_int(1, 4),
                    ...$this->openingHours(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $parkingSpotNames[] = $name;
            }

            DB::table('parking_spots')->insert($parkingSpots);

            $parkingSpotIds = DB::table('parking_spots')
                ->whereIn('name', $parkingSpotNames)
                ->pluck('id', 'name');
            $rates = [];

            foreach ($parkingSpotNames as $name) {
                $rates = [...$rates, ...$this->ratesFor((int) $parkingSpotIds[$name], $now)];
            }

            DB::table('parking_spot_rates')->insert($rates);
        }

        $this->command?->info("Created {$parkingSpotCount} Japanese sample parking spots.");
    }

    /**
     * @return array<string, list<array{postalcode_id: int, prefecture: string, city: string, town: string, latitude: float, longitude: float}>>
     */
    private function locationsByPrefecture(int $parkingSpotCount): array
    {
        $sourcePath = $this->sourcePath();
        $prefectures = $this->prefecturesInSource($sourcePath);

        if ($prefectures === []) {
            throw new LogicException('JapanParkingSpotSeeder requires Japanese address and coordinate data.');
        }

        $sampleSize = (int) ceil($parkingSpotCount / count($prefectures))
            + $this->nonNegativeIntegerSetting('parking_spot.sample_data.location_buffer_per_prefecture');

        return $this->persistLocations($this->sampleLocationsByPrefecture($sourcePath, $prefectures, $sampleSize));
    }

    private function sourcePath(): string
    {
        $path = config('parking_spot.sample_data.postalcode_csv_path');

        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            throw new LogicException('JapanParkingSpotSeeder requires a readable postal-code coordinate CSV.');
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private function prefecturesInSource(string $sourcePath): array
    {
        $prefectures = [];

        $this->eachSourceLocation($sourcePath, function (array $location) use (&$prefectures): void {
            $prefectures[$location['prefecture']] = true;
        });

        return array_keys($prefectures);
    }

    /**
     * @param  list<string>  $prefectures
     * @return array<string, list<array{postalcode: string, prefecture: string, city: string, town: string, latitude: float, longitude: float}>>
     */
    private function sampleLocationsByPrefecture(string $sourcePath, array $prefectures, int $sampleSize): array
    {
        $samples = array_fill_keys($prefectures, []);
        $seenCounts = array_fill_keys($prefectures, 0);

        $this->eachSourceLocation($sourcePath, function (array $location) use (&$samples, &$seenCounts, $sampleSize): void {
            $prefecture = $location['prefecture'];
            $seenCounts[$prefecture]++;

            if (count($samples[$prefecture]) < $sampleSize) {
                $samples[$prefecture][] = $location;

                return;
            }

            // Reservoir sampling keeps every source location equally likely without loading the CSV into memory.
            $replacementIndex = random_int(0, $seenCounts[$prefecture] - 1);

            if ($replacementIndex < $sampleSize) {
                $samples[$prefecture][$replacementIndex] = $location;
            }
        });

        return $samples;
    }

    /**
     * @param  array<string, list<array{postalcode: string, prefecture: string, city: string, town: string, latitude: float, longitude: float}>>  $sourceLocationsByPrefecture
     * @return array<string, list<array{postalcode_id: int, prefecture: string, city: string, town: string, latitude: float, longitude: float}>>
     */
    private function persistLocations(array $sourceLocationsByPrefecture): array
    {
        $sourceLocations = array_merge(...array_values($sourceLocationsByPrefecture));
        $prefectureIds = $this->prefectureIds(array_keys($sourceLocationsByPrefecture));
        $cityIds = $this->cityIds($sourceLocations, $prefectureIds);
        $postalcodeIds = $this->postalcodeIds($sourceLocations, $cityIds);
        $locationsByPrefecture = array_fill_keys(array_keys($sourceLocationsByPrefecture), []);

        foreach ($sourceLocations as $location) {
            $cityId = $cityIds[$this->cityKey($location['prefecture'], $location['city'])];
            $postalcode = $postalcodeIds[$this->postalcodeKey($location['postalcode'], $cityId)] ?? null;

            if ($postalcode === null || ! $postalcode['is_active']) {
                continue;
            }

            $locationsByPrefecture[$location['prefecture']][] = [
                'postalcode_id' => $postalcode['id'],
                'prefecture' => $location['prefecture'],
                'city' => $location['city'],
                'town' => $location['town'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ];
        }

        return $locationsByPrefecture;
    }

    /**
     * @param  list<string>  $prefectureNames
     * @return array<string, int>
     */
    private function prefectureIds(array $prefectureNames): array
    {
        $prefectureIds = Prefecture::query()
            ->whereIn('name', $prefectureNames)
            ->pluck('id', 'name')
            ->map(static fn (int $id): int => $id)
            ->all();

        foreach (array_diff($prefectureNames, array_keys($prefectureIds)) as $name) {
            $prefectureIds[$name] = Prefecture::query()->create([
                'name' => $name,
                'name_kana' => '',
            ])->id;
        }

        return $prefectureIds;
    }

    /**
     * @param  list<array{postalcode: string, prefecture: string, city: string, town: string, latitude: float, longitude: float}>  $sourceLocations
     * @param  array<string, int>  $prefectureIds
     * @return array<string, int>
     */
    private function cityIds(array $sourceLocations, array $prefectureIds): array
    {
        $cityDefinitions = [];

        foreach ($sourceLocations as $location) {
            $cityDefinitions[$this->cityKey($location['prefecture'], $location['city'])] = $location;
        }

        $cityIds = City::query()
            ->whereIn('prefecture_id', array_values($prefectureIds))
            ->get(['id', 'prefecture_id', 'name'])
            ->mapWithKeys(fn (City $city): array => [
                $this->cityIdKey($city->prefecture_id, $city->name) => $city->id,
            ])
            ->all();

        foreach ($cityDefinitions as $location) {
            $prefectureId = $prefectureIds[$location['prefecture']];
            $cityIdKey = $this->cityIdKey($prefectureId, $location['city']);

            if (isset($cityIds[$cityIdKey])) {
                continue;
            }

            $cityIds[$cityIdKey] = City::query()->create([
                'prefecture_id' => $prefectureId,
                'name' => $location['city'],
                'name_kana' => '',
            ])->id;
        }

        return collect($cityDefinitions)
            ->mapWithKeys(fn (array $location, string $key): array => [
                $key => $cityIds[$this->cityIdKey($prefectureIds[$location['prefecture']], $location['city'])],
            ])
            ->all();
    }

    /**
     * @param  list<array{postalcode: string, prefecture: string, city: string, town: string, latitude: float, longitude: float}>  $sourceLocations
     * @param  array<string, int>  $cityIds
     * @return array<string, array{id: int, is_active: bool}>
     */
    private function postalcodeIds(array $sourceLocations, array $cityIds): array
    {
        $postalcodeValues = array_values(array_unique(array_column($sourceLocations, 'postalcode')));
        $postalcodeIds = $this->existingPostalcodes($postalcodeValues);
        $missingPostalcodes = [];
        $now = now();

        foreach ($sourceLocations as $location) {
            $cityId = $cityIds[$this->cityKey($location['prefecture'], $location['city'])];
            $key = $this->postalcodeKey($location['postalcode'], $cityId);

            if (isset($postalcodeIds[$key]) || isset($missingPostalcodes[$key])) {
                continue;
            }

            $missingPostalcodes[$key] = [
                'postalcode' => $location['postalcode'],
                'city_id' => $cityId,
                'name' => $location['town'],
                'name_kana' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($missingPostalcodes), 500) as $postalcodeChunk) {
            DB::table('postalcodes')->insert($postalcodeChunk);
        }

        return $this->existingPostalcodes($postalcodeValues);
    }

    /**
     * @param  list<string>  $postalcodeValues
     * @return array<string, array{id: int, is_active: bool}>
     */
    private function existingPostalcodes(array $postalcodeValues): array
    {
        $postalcodeIds = [];

        foreach (array_chunk($postalcodeValues, 500) as $postalcodeChunk) {
            $postalcodeIds = [
                ...$postalcodeIds,
                ...Postalcode::query()
                    ->whereIn('postalcode', $postalcodeChunk)
                    ->get(['id', 'postalcode', 'city_id', 'is_active'])
                    ->mapWithKeys(fn (Postalcode $postalcode): array => [
                        $this->postalcodeKey($postalcode->postalcode, $postalcode->city_id) => [
                            'id' => $postalcode->id,
                            'is_active' => $postalcode->is_active,
                        ],
                    ])
                    ->all(),
            ];
        }

        return $postalcodeIds;
    }

    /**
     * @param  list<string>  $prefectures
     * @return list<string>
     */
    private function prefectureQueue(array $prefectures, int $parkingSpotCount): array
    {
        $queue = [];

        while (count($queue) < $parkingSpotCount) {
            $cycle = $prefectures;
            shuffle($cycle);

            foreach ($cycle as $prefecture) {
                $queue[] = $prefecture;
            }
        }

        return array_slice($queue, 0, $parkingSpotCount);
    }

    /**
     * @param  array{prefecture: string, city: string, town: string}  $location
     */
    private function addressFor(array $location): string
    {
        return sprintf(
            '%s%s%s%d丁目%d-%d',
            $location['prefecture'],
            $location['city'],
            $location['town'],
            random_int(1, 9),
            random_int(1, 30),
            random_int(1, 30),
        );
    }

    /**
     * @return array{opening_time: string, closing_time: string}
     */
    private function openingHours(): array
    {
        $openingHours = [
            ['opening_time' => '00:00:00', 'closing_time' => '00:00:00'],
            ['opening_time' => '05:00:00', 'closing_time' => '23:00:00'],
            ['opening_time' => '06:00:00', 'closing_time' => '22:00:00'],
            ['opening_time' => '07:00:00', 'closing_time' => '21:00:00'],
            ['opening_time' => '09:00:00', 'closing_time' => '20:00:00'],
        ];

        return $openingHours[array_rand($openingHours)];
    }

    /**
     * @return list<array{parking_spot_id: int, day_type: string, start_time: string, end_time: string, unit_minutes: int, rate: int, free_minutes: int, max_rate: int|null, created_at: CarbonInterface, updated_at: CarbonInterface}>
     */
    private function ratesFor(int $parkingSpotId, CarbonInterface $now): array
    {
        $dayTypes = [
            ['day_type' => '全日', 'start_time' => '00:00:00', 'end_time' => '00:00:00'],
            ['day_type' => '平日', 'start_time' => '08:00:00', 'end_time' => '20:00:00'],
            ['day_type' => '土日祝', 'start_time' => '08:00:00', 'end_time' => '20:00:00'],
        ];
        $count = random_int(0, 1) === 0 ? 1 : 3;
        $rates = [];

        foreach (array_slice($dayTypes, 0, $count) as $dayType) {
            $unitOptions = [30, 60, 120];
            $rateOptions = [100, 150, 200, 300];
            $unitMinutes = $unitOptions[array_rand($unitOptions)];
            $rate = $rateOptions[array_rand($rateOptions)];
            $maxRate = random_int(0, 4) === 0 ? null : $rate * random_int(5, 12);

            $rates[] = [
                'parking_spot_id' => $parkingSpotId,
                ...$dayType,
                'unit_minutes' => $unitMinutes,
                'rate' => $rate,
                'free_minutes' => random_int(0, 3) === 0 ? 30 : 0,
                'max_rate' => $maxRate,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rates;
    }

    /**
     * @param  callable(array{postalcode: string, prefecture: string, city: string, town: string, latitude: float, longitude: float}): void  $callback
     */
    private function eachSourceLocation(string $sourcePath, callable $callback): void
    {
        $handle = fopen($sourcePath, 'rb');

        if ($handle === false) {
            throw new LogicException('JapanParkingSpotSeeder could not open the postal-code coordinate CSV.');
        }

        $lineNumber = 0;

        try {
            while (($columns = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $lineNumber++;

                if ($columns === [null] || $columns === ['']) {
                    continue;
                }

                if (count($columns) !== 6) {
                    throw new RuntimeException("Postal-code coordinate CSV line {$lineNumber} has an invalid column count.");
                }

                $columns = array_map(
                    static fn ($value): string => trim((string) $value),
                    $columns,
                );

                if (preg_match('/^\d{7}$/', $columns[0]) !== 1
                    || $columns[1] === ''
                    || $columns[2] === ''
                    || ! is_numeric($columns[4])
                    || ! is_numeric($columns[5])) {
                    throw new RuntimeException("Postal-code coordinate CSV line {$lineNumber} has invalid required values.");
                }

                $latitude = (float) $columns[4];
                $longitude = (float) $columns[5];

                if (! is_finite($latitude) || ! is_finite($longitude)
                    || $latitude < 20 || $latitude > 46
                    || $longitude < 122 || $longitude > 154) {
                    throw new RuntimeException("Postal-code coordinate CSV line {$lineNumber} has coordinates outside Japan.");
                }

                $callback([
                    'postalcode' => $columns[0],
                    'prefecture' => $columns[1],
                    'city' => $columns[2],
                    'town' => $columns[3],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            }
        } finally {
            fclose($handle);
        }
    }

    private function cityKey(string $prefecture, string $city): string
    {
        return $prefecture."\0".$city;
    }

    private function cityIdKey(int $prefectureId, string $city): string
    {
        return $prefectureId."\0".$city;
    }

    private function postalcodeKey(string $postalcode, int $cityId): string
    {
        return $postalcode."\0".$cityId;
    }

    private function positiveIntegerSetting(string $key): int
    {
        $value = filter_var(config($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($value === false) {
            throw new LogicException("{$key} must be a positive integer.");
        }

        return $value;
    }

    private function nonNegativeIntegerSetting(string $key): int
    {
        $value = filter_var(config($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($value === false) {
            throw new LogicException("{$key} must be a non-negative integer.");
        }

        return $value;
    }

    private function randomizedCoordinate(float $coordinate): float
    {
        return round($coordinate + (random_int(-600, 600) / 100_000), 6);
    }
}
