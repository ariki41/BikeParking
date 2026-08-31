<?php

namespace App\Services;

use App\Models\City;
use App\Models\Postalcode;
use App\Models\Prefecture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class PostalCodeImportService
{
    /**
     * @param  null|callable(array{
     *     postalcode: string,
     *     prefecture: string,
     *     prefecture_kana: string,
     *     city: string,
     *     city_kana: string,
     *     town: string,
     *     town_kana: string
     * }): array{
     *     postalcode: string,
     *     prefecture: string,
     *     prefecture_kana: string,
     *     city: string,
     *     city_kana: string,
     *     town: string,
     *     town_kana: string
     * }  $recordNormalizer
     * @return array{source_records: int, active_postalcodes: int, inactive_postalcodes: int}
     */
    public function downloadAndImport(string $url, ?callable $recordNormalizer = null): array
    {
        $archivePath = null;
        $csvPath = null;

        try {
            $archivePath = $this->temporaryPath('postal-code-archive-');
            $csvPath = $this->temporaryPath('postal-code-csv-');

            $this->download($url, $archivePath);
            $this->extractCsv($archivePath, $csvPath);

            $analysis = $this->analyzeCsv($csvPath);

            return $this->importCsv($csvPath, $analysis, $recordNormalizer);
        } finally {
            foreach ([$archivePath, $csvPath] as $path) {
                if (is_string($path) && is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function download(string $url, string $archivePath): void
    {
        $response = Http::accept('application/zip')
            ->withUserAgent('BikeParking postal-code-sync')
            ->timeout((int) config('postal_codes.http.timeout_seconds'))
            ->retry(
                (int) config('postal_codes.http.retry_times'),
                (int) config('postal_codes.http.retry_sleep_milliseconds'),
            )
            ->get($url);

        $response->throw();

        $contents = $response->body();
        $size = strlen($contents);

        if ($size === 0) {
            throw new RuntimeException('ダウンロードしたZIPファイルが空です。');
        }

        if ($size > (int) config('postal_codes.maximum_archive_bytes')) {
            throw new RuntimeException('ダウンロードしたZIPファイルが上限サイズを超えています。');
        }

        if (file_put_contents($archivePath, $contents) !== $size) {
            throw new RuntimeException('ダウンロードしたZIPファイルを一時保存できませんでした。');
        }
    }

    private function extractCsv(string $archivePath, string $csvPath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('郵便番号データの展開にはPHPのzip拡張が必要です。');
        }

        $archive = new ZipArchive;

        if ($archive->open($archivePath) !== true) {
            throw new RuntimeException('ダウンロードしたファイルは有効なZIPではありません。');
        }

        $memberName = null;
        $expectedMemberName = strtolower((string) config('postal_codes.csv_filename'));

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);

            if (is_string($name) && strtolower(basename($name)) === $expectedMemberName) {
                $memberName = $name;
                break;
            }
        }

        if ($memberName === null) {
            $archive->close();

            throw new RuntimeException('ZIP内に郵便番号CSVが見つかりません。');
        }

        $stat = $archive->statName($memberName);
        $maximumCsvBytes = (int) config('postal_codes.maximum_csv_bytes');

        if (! is_array($stat) || ($stat['size'] ?? 0) < 1 || $stat['size'] > $maximumCsvBytes) {
            $archive->close();

            throw new RuntimeException('ZIP内の郵便番号CSVのサイズが正しくありません。');
        }

        $input = $archive->getStream($memberName);
        $output = fopen($csvPath, 'wb');

        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }

            if (is_resource($output)) {
                fclose($output);
            }

            $archive->close();

            throw new RuntimeException('ZIP内の郵便番号CSVを開けませんでした。');
        }

        try {
            $copiedBytes = stream_copy_to_stream($input, $output, $maximumCsvBytes + 1);
        } finally {
            fclose($input);
            fclose($output);
            $archive->close();
        }

        if ($copiedBytes === false || $copiedBytes < 1 || $copiedBytes > $maximumCsvBytes) {
            throw new RuntimeException('ZIP内の郵便番号CSVを展開できませんでした。');
        }
    }

    /**
     * @return array{
     *     source_records: int,
     *     prefectures: array<string, string>,
     *     cities: array<string, array{prefecture: string, name: string, name_kana: string}>
     * }
     */
    private function analyzeCsv(string $csvPath): array
    {
        $sourceRecords = 0;
        $prefectures = [];
        $cities = [];

        $this->eachRecord($csvPath, function (array $record) use (&$sourceRecords, &$prefectures, &$cities): void {
            $sourceRecords++;
            $prefectures[$record['prefecture']] = $record['prefecture_kana'];
            $cities[$this->cityKey($record['prefecture'], $record['city'])] = [
                'prefecture' => $record['prefecture'],
                'name' => $record['city'],
                'name_kana' => $record['city_kana'],
            ];
        });

        if ($sourceRecords < (int) config('postal_codes.minimum_records')) {
            throw new RuntimeException(sprintf(
                'CSVの有効レコード数が少なすぎます（%s件）。',
                number_format($sourceRecords),
            ));
        }

        if (count($prefectures) < (int) config('postal_codes.minimum_prefectures')) {
            throw new RuntimeException(sprintf(
                'CSVに含まれる都道府県数が少なすぎます（%s件）。',
                number_format(count($prefectures)),
            ));
        }

        return [
            'source_records' => $sourceRecords,
            'prefectures' => $prefectures,
            'cities' => $cities,
        ];
    }

    /**
     * @param  array{
     *     source_records: int,
     *     prefectures: array<string, string>,
     *     cities: array<string, array{prefecture: string, name: string, name_kana: string}>
     * }  $analysis
     * @return array{source_records: int, active_postalcodes: int, inactive_postalcodes: int}
     */
    private function importCsv(string $csvPath, array $analysis, ?callable $recordNormalizer): array
    {
        return DB::transaction(function () use ($csvPath, $analysis, $recordNormalizer): array {
            $prefectureIds = [];

            foreach ($analysis['prefectures'] as $name => $nameKana) {
                $prefecture = Prefecture::query()->updateOrCreate(
                    ['name' => $name],
                    ['name_kana' => $nameKana],
                );
                $prefectureIds[$name] = $prefecture->id;
            }

            Prefecture::query()->updateOrCreate(
                ['name' => '海外'],
                ['name_kana' => 'カイガイ'],
            );

            $cityIds = [];

            foreach ($analysis['cities'] as $key => $attributes) {
                $city = City::query()->updateOrCreate(
                    [
                        'prefecture_id' => $prefectureIds[$attributes['prefecture']],
                        'name' => $attributes['name'],
                    ],
                    ['name_kana' => $attributes['name_kana']],
                );
                $cityIds[$key] = $city->id;
            }

            $now = now();
            Postalcode::query()
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => $now]);

            $seen = [];
            $batch = [];
            $activePostalcodes = 0;
            $batchSize = (int) config('postal_codes.import_batch_size');

            $this->eachRecord($csvPath, function (array $record) use (
                &$activePostalcodes,
                &$batch,
                &$seen,
                $batchSize,
                $cityIds,
                $now,
                $recordNormalizer,
            ): void {
                if ($recordNormalizer !== null) {
                    $record = $recordNormalizer($record);
                }

                $cityId = $cityIds[$this->cityKey($record['prefecture'], $record['city'])];
                $postalcodeKey = $record['postalcode']."\0".$cityId;

                // The source can contain several towns for one postal code and city.
                // Address completion can return only one address, so retain the first official row.
                if (isset($seen[$postalcodeKey])) {
                    return;
                }

                $seen[$postalcodeKey] = true;
                $activePostalcodes++;
                $batch[] = [
                    'postalcode' => $record['postalcode'],
                    'city_id' => $cityId,
                    'name' => $record['town'],
                    'name_kana' => $record['town_kana'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    $this->upsertPostalcodes($batch);
                    $batch = [];
                }
            });

            $this->upsertPostalcodes($batch);

            return [
                'source_records' => $analysis['source_records'],
                'active_postalcodes' => $activePostalcodes,
                'inactive_postalcodes' => Postalcode::query()->where('is_active', false)->count(),
            ];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertPostalcodes(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table('postalcodes')->upsert(
            $rows,
            ['postalcode', 'city_id'],
            ['name', 'name_kana', 'is_active', 'updated_at'],
        );
    }

    /**
     * @param  callable(array{
     *     postalcode: string,
     *     prefecture: string,
     *     prefecture_kana: string,
     *     city: string,
     *     city_kana: string,
     *     town: string,
     *     town_kana: string
     * }): void  $callback
     */
    private function eachRecord(string $csvPath, callable $callback): void
    {
        $handle = fopen($csvPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('展開した郵便番号CSVを開けませんでした。');
        }

        $lineNumber = 0;

        try {
            while (($columns = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $lineNumber++;

                if ($columns === [null] || $columns === ['']) {
                    continue;
                }

                if (count($columns) !== 15) {
                    throw new RuntimeException("CSV {$lineNumber}行目の列数が正しくありません。");
                }

                $columns = array_map(
                    static fn ($value): string => trim((string) $value),
                    $columns,
                );

                if (! mb_check_encoding(implode('', $columns), 'UTF-8')) {
                    throw new RuntimeException("CSV {$lineNumber}行目がUTF-8ではありません。");
                }

                if (preg_match('/^\d{5}$/', $columns[0]) !== 1
                    || preg_match('/^\d{7}$/', $columns[2]) !== 1
                    || $columns[6] === ''
                    || $columns[7] === '') {
                    throw new RuntimeException("CSV {$lineNumber}行目の必須項目が正しくありません。");
                }

                $callback([
                    'postalcode' => $columns[2],
                    'prefecture' => $columns[6],
                    'prefecture_kana' => $columns[3],
                    'city' => $columns[7],
                    'city_kana' => $columns[4],
                    'town' => $columns[8],
                    'town_kana' => $columns[5],
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

    private function temporaryPath(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if ($path === false) {
            throw new RuntimeException('郵便番号同期用の一時ファイルを作成できませんでした。');
        }

        return $path;
    }
}
