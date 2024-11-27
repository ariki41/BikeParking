<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Postalcode;
use App\Models\Prefecture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportPrefCityPostalcode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-pref-city-postalcode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '都道府県、市区町村、郵便番号データをインポートする';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = 'x-ken-all.csv';
        if (Storage::exists($filePath)) {
            $file = Storage::get($filePath);
            $lines = explode(PHP_EOL, $file);
            DB::transaction(function () use ($lines) {
                foreach ($lines as $line) {
                    $columns = mb_convert_encoding(str_getcsv($line), 'UTF-8', 'SJIS');

                    if (is_null($columns[0])) {
                        continue;
                    }

                    $prefectureName = $columns[6];
                    $prefectureNameKana = mb_convert_kana($columns[3]);
                    $cityName = $columns[7];
                    $cityNamekana = mb_convert_kana($columns[4]);
                    $townName = $columns[8];
                    $townNameKana = mb_convert_kana($columns[5]);
                    $postalcode = $columns[2];

                    $prefecture = Prefecture::firstOrCreate(
                        ['name' => $prefectureName],
                        ['name_kana' => $prefectureNameKana]
                    );

                    $city = City::firstOrCreate(
                        ['name' => $cityName, 'prefecture_id' => $prefecture->id],
                        ['name_kana' => $cityNamekana]
                    );

                    Postalcode::firstOrCreate(
                        ['postalcode' => $postalcode, 'city_id' => $city->id],
                        ['name' => $townName, 'name_kana' => $townNameKana]
                    );
                }
            });

            Prefecture::firstOrCreate([
                'name' => '海外', 'name_kana' => 'カイガイ',
            ]);
        }
    }
}
