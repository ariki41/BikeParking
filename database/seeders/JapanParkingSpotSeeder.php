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

class JapanParkingSpotSeeder extends Seeder
{
    private const PARKING_SPOT_COUNT = 10_000;

    private const INSERT_CHUNK_SIZE = 500;

    /** @var list<string> */
    private const PARKING_SPOT_NAME_SUFFIXES = [
        '駅前駐輪場',
        '中央駐輪場',
        '市役所前駐輪場',
        '公園前駐輪場',
        '商店街駐輪場',
    ];

    /**
     * Seed representative locations so a fresh installation can generate
     * nationwide sample data without requiring the optional full address import.
     *
     * @var list<array{prefecture: string, prefecture_kana: string, city: string, city_kana: string, town: string, town_kana: string, postalcode: string, latitude: float, longitude: float}>
     */
    private const LOCATIONS = [
        ['prefecture' => '北海道', 'prefecture_kana' => 'ホッカイドウ', 'city' => '札幌市中央区', 'city_kana' => 'サッポロシチュウオウク', 'town' => '北一条西', 'town_kana' => 'キタ1ジョウニシ', 'postalcode' => '0600001', 'latitude' => 43.064170, 'longitude' => 141.346940],
        ['prefecture' => '青森県', 'prefecture_kana' => 'アオモリケン', 'city' => '青森市', 'city_kana' => 'アオモリシ', 'town' => '長島', 'town_kana' => 'ナガシマ', 'postalcode' => '0300801', 'latitude' => 40.824440, 'longitude' => 140.740000],
        ['prefecture' => '岩手県', 'prefecture_kana' => 'イワテケン', 'city' => '盛岡市', 'city_kana' => 'モリオカシ', 'town' => '内丸', 'town_kana' => 'ウチマル', 'postalcode' => '0200023', 'latitude' => 39.703610, 'longitude' => 141.152500],
        ['prefecture' => '宮城県', 'prefecture_kana' => 'ミヤギケン', 'city' => '仙台市青葉区', 'city_kana' => 'センダイシアオバク', 'town' => '本町', 'town_kana' => 'ホンチョウ', 'postalcode' => '9800014', 'latitude' => 38.268840, 'longitude' => 140.872100],
        ['prefecture' => '秋田県', 'prefecture_kana' => 'アキタケン', 'city' => '秋田市', 'city_kana' => 'アキタシ', 'town' => '山王', 'town_kana' => 'サンノウ', 'postalcode' => '0100951', 'latitude' => 39.718600, 'longitude' => 140.102400],
        ['prefecture' => '山形県', 'prefecture_kana' => 'ヤマガタケン', 'city' => '山形市', 'city_kana' => 'ヤマガタシ', 'town' => '十日町', 'town_kana' => 'トオカマチ', 'postalcode' => '9900031', 'latitude' => 38.240400, 'longitude' => 140.363300],
        ['prefecture' => '福島県', 'prefecture_kana' => 'フクシマケン', 'city' => '福島市', 'city_kana' => 'フクシマシ', 'town' => '杉妻町', 'town_kana' => 'スギツマチョウ', 'postalcode' => '9608065', 'latitude' => 37.750300, 'longitude' => 140.467600],
        ['prefecture' => '茨城県', 'prefecture_kana' => 'イバラキケン', 'city' => '水戸市', 'city_kana' => 'ミトシ', 'town' => '笠原町', 'town_kana' => 'カサハラチョウ', 'postalcode' => '3100852', 'latitude' => 36.341800, 'longitude' => 140.446800],
        ['prefecture' => '栃木県', 'prefecture_kana' => 'トチギケン', 'city' => '宇都宮市', 'city_kana' => 'ウツノミヤシ', 'town' => '塙田', 'town_kana' => 'ハナワダ', 'postalcode' => '3200027', 'latitude' => 36.565800, 'longitude' => 139.883600],
        ['prefecture' => '群馬県', 'prefecture_kana' => 'グンマケン', 'city' => '前橋市', 'city_kana' => 'マエバシシ', 'town' => '大手町', 'town_kana' => 'オオテマチ', 'postalcode' => '3710026', 'latitude' => 36.391100, 'longitude' => 139.060800],
        ['prefecture' => '埼玉県', 'prefecture_kana' => 'サイタマケン', 'city' => 'さいたま市浦和区', 'city_kana' => 'サイタマシウラワク', 'town' => '高砂', 'town_kana' => 'タカサゴ', 'postalcode' => '3300063', 'latitude' => 35.857000, 'longitude' => 139.648900],
        ['prefecture' => '千葉県', 'prefecture_kana' => 'チバケン', 'city' => '千葉市中央区', 'city_kana' => 'チバシチュウオウク', 'town' => '市場町', 'town_kana' => 'イチバチョウ', 'postalcode' => '2600855', 'latitude' => 35.607400, 'longitude' => 140.106500],
        ['prefecture' => '東京都', 'prefecture_kana' => 'トウキョウト', 'city' => '千代田区', 'city_kana' => 'チヨダク', 'town' => '千代田', 'town_kana' => 'チヨダ', 'postalcode' => '1000001', 'latitude' => 35.681236, 'longitude' => 139.767125],
        ['prefecture' => '神奈川県', 'prefecture_kana' => 'カナガワケン', 'city' => '横浜市西区', 'city_kana' => 'ヨコハマシニシク', 'town' => 'みなとみらい', 'town_kana' => 'ミナトミライ', 'postalcode' => '2200012', 'latitude' => 35.457390, 'longitude' => 139.631180],
        ['prefecture' => '新潟県', 'prefecture_kana' => 'ニイガタケン', 'city' => '新潟市中央区', 'city_kana' => 'ニイガタシチュウオウク', 'town' => '学校町通', 'town_kana' => 'ガッコウチョウドオリ', 'postalcode' => '9518126', 'latitude' => 37.902220, 'longitude' => 139.023610],
        ['prefecture' => '富山県', 'prefecture_kana' => 'トヤマケン', 'city' => '富山市', 'city_kana' => 'トヤマシ', 'town' => '新総曲輪', 'town_kana' => 'シンソウガワ', 'postalcode' => '9300081', 'latitude' => 36.695300, 'longitude' => 137.211300],
        ['prefecture' => '石川県', 'prefecture_kana' => 'イシカワケン', 'city' => '金沢市', 'city_kana' => 'カナザワシ', 'town' => '広坂', 'town_kana' => 'ヒロサカ', 'postalcode' => '9200962', 'latitude' => 36.561330, 'longitude' => 136.656200],
        ['prefecture' => '福井県', 'prefecture_kana' => 'フクイケン', 'city' => '福井市', 'city_kana' => 'フクイシ', 'town' => '大手', 'town_kana' => 'オオテ', 'postalcode' => '9100005', 'latitude' => 36.065200, 'longitude' => 136.221700],
        ['prefecture' => '山梨県', 'prefecture_kana' => 'ヤマナシケン', 'city' => '甲府市', 'city_kana' => 'コウフシ', 'town' => '丸の内', 'town_kana' => 'マルノウチ', 'postalcode' => '4000031', 'latitude' => 35.663900, 'longitude' => 138.568300],
        ['prefecture' => '長野県', 'prefecture_kana' => 'ナガノケン', 'city' => '長野市', 'city_kana' => 'ナガノシ', 'town' => '大字南長野', 'town_kana' => 'オオアザミナミナガノ', 'postalcode' => '3800835', 'latitude' => 36.648600, 'longitude' => 138.194900],
        ['prefecture' => '岐阜県', 'prefecture_kana' => 'ギフケン', 'city' => '岐阜市', 'city_kana' => 'ギフシ', 'town' => '薮田南', 'town_kana' => 'ヤブタミナミ', 'postalcode' => '5008384', 'latitude' => 35.423300, 'longitude' => 136.760700],
        ['prefecture' => '静岡県', 'prefecture_kana' => 'シズオカケン', 'city' => '静岡市葵区', 'city_kana' => 'シズオカシアオイク', 'town' => '追手町', 'town_kana' => 'オウテマチ', 'postalcode' => '4200853', 'latitude' => 34.976900, 'longitude' => 138.383100],
        ['prefecture' => '愛知県', 'prefecture_kana' => 'アイチケン', 'city' => '名古屋市中区', 'city_kana' => 'ナゴヤシナカク', 'town' => '三の丸', 'town_kana' => 'サンノマル', 'postalcode' => '4600001', 'latitude' => 35.180280, 'longitude' => 136.906670],
        ['prefecture' => '三重県', 'prefecture_kana' => 'ミエケン', 'city' => '津市', 'city_kana' => 'ツシ', 'town' => '広明町', 'town_kana' => 'コウメイチョウ', 'postalcode' => '5140006', 'latitude' => 34.730300, 'longitude' => 136.508600],
        ['prefecture' => '滋賀県', 'prefecture_kana' => 'シガケン', 'city' => '大津市', 'city_kana' => 'オオツシ', 'town' => '京町', 'town_kana' => 'キョウマチ', 'postalcode' => '5200044', 'latitude' => 35.004500, 'longitude' => 135.868600],
        ['prefecture' => '京都府', 'prefecture_kana' => 'キョウトフ', 'city' => '京都市中京区', 'city_kana' => 'キョウトシナカギョウク', 'town' => '下本能寺前町', 'town_kana' => 'シモホンノウジマエチョウ', 'postalcode' => '6048571', 'latitude' => 35.011640, 'longitude' => 135.768030],
        ['prefecture' => '大阪府', 'prefecture_kana' => 'オオサカフ', 'city' => '大阪市北区', 'city_kana' => 'オオサカシキタク', 'town' => '中之島', 'town_kana' => 'ナカノシマ', 'postalcode' => '5300005', 'latitude' => 34.693740, 'longitude' => 135.502180],
        ['prefecture' => '兵庫県', 'prefecture_kana' => 'ヒョウゴケン', 'city' => '神戸市中央区', 'city_kana' => 'コウベシチュウオウク', 'town' => '加納町', 'town_kana' => 'カノウチョウ', 'postalcode' => '6500001', 'latitude' => 34.691300, 'longitude' => 135.183000],
        ['prefecture' => '奈良県', 'prefecture_kana' => 'ナラケン', 'city' => '奈良市', 'city_kana' => 'ナラシ', 'town' => '二条大路南', 'town_kana' => 'ニジョウオオジミナミ', 'postalcode' => '6308012', 'latitude' => 34.685100, 'longitude' => 135.832800],
        ['prefecture' => '和歌山県', 'prefecture_kana' => 'ワカヤマケン', 'city' => '和歌山市', 'city_kana' => 'ワカヤマシ', 'town' => '小松原通', 'town_kana' => 'コマツバラドオリ', 'postalcode' => '6408269', 'latitude' => 34.226000, 'longitude' => 135.167500],
        ['prefecture' => '鳥取県', 'prefecture_kana' => 'トットリケン', 'city' => '鳥取市', 'city_kana' => 'トットリシ', 'town' => '東町', 'town_kana' => 'ヒガシマチ', 'postalcode' => '6800011', 'latitude' => 35.503900, 'longitude' => 134.238300],
        ['prefecture' => '島根県', 'prefecture_kana' => 'シマネケン', 'city' => '松江市', 'city_kana' => 'マツエシ', 'town' => '殿町', 'town_kana' => 'トノマチ', 'postalcode' => '6900887', 'latitude' => 35.472300, 'longitude' => 133.050500],
        ['prefecture' => '岡山県', 'prefecture_kana' => 'オカヤマケン', 'city' => '岡山市北区', 'city_kana' => 'オカヤマシキタク', 'town' => '内山下', 'town_kana' => 'ウチサンゲ', 'postalcode' => '7000824', 'latitude' => 34.661800, 'longitude' => 133.935000],
        ['prefecture' => '広島県', 'prefecture_kana' => 'ヒロシマケン', 'city' => '広島市中区', 'city_kana' => 'ヒロシマシナカク', 'town' => '基町', 'town_kana' => 'モトマチ', 'postalcode' => '7300011', 'latitude' => 34.396560, 'longitude' => 132.459620],
        ['prefecture' => '山口県', 'prefecture_kana' => 'ヤマグチケン', 'city' => '山口市', 'city_kana' => 'ヤマグチシ', 'town' => '滝町', 'town_kana' => 'タキマチ', 'postalcode' => '7530071', 'latitude' => 34.185900, 'longitude' => 131.471400],
        ['prefecture' => '徳島県', 'prefecture_kana' => 'トクシマケン', 'city' => '徳島市', 'city_kana' => 'トクシマシ', 'town' => '万代町', 'town_kana' => 'バンダイチョウ', 'postalcode' => '7700941', 'latitude' => 34.065800, 'longitude' => 134.559300],
        ['prefecture' => '香川県', 'prefecture_kana' => 'カガワケン', 'city' => '高松市', 'city_kana' => 'タカマツシ', 'town' => '番町', 'town_kana' => 'バンチョウ', 'postalcode' => '7600017', 'latitude' => 34.340100, 'longitude' => 134.043400],
        ['prefecture' => '愛媛県', 'prefecture_kana' => 'エヒメケン', 'city' => '松山市', 'city_kana' => 'マツヤマシ', 'town' => '一番町', 'town_kana' => 'イチバンチョウ', 'postalcode' => '7900001', 'latitude' => 33.841600, 'longitude' => 132.765700],
        ['prefecture' => '高知県', 'prefecture_kana' => 'コウチケン', 'city' => '高知市', 'city_kana' => 'コウチシ', 'town' => '本町', 'town_kana' => 'ホンマチ', 'postalcode' => '7800870', 'latitude' => 33.559700, 'longitude' => 133.531100],
        ['prefecture' => '福岡県', 'prefecture_kana' => 'フクオカケン', 'city' => '福岡市博多区', 'city_kana' => 'フクオカシハカタク', 'town' => '博多駅前', 'town_kana' => 'ハカタエキマエ', 'postalcode' => '8120011', 'latitude' => 33.590350, 'longitude' => 130.420800],
        ['prefecture' => '佐賀県', 'prefecture_kana' => 'サガケン', 'city' => '佐賀市', 'city_kana' => 'サガシ', 'town' => '城内', 'town_kana' => 'ジョウナイ', 'postalcode' => '8400041', 'latitude' => 33.249400, 'longitude' => 130.298800],
        ['prefecture' => '長崎県', 'prefecture_kana' => 'ナガサキケン', 'city' => '長崎市', 'city_kana' => 'ナガサキシ', 'town' => '尾上町', 'town_kana' => 'オノウエマチ', 'postalcode' => '8500058', 'latitude' => 32.744800, 'longitude' => 129.873700],
        ['prefecture' => '熊本県', 'prefecture_kana' => 'クマモトケン', 'city' => '熊本市中央区', 'city_kana' => 'クマモトシチュウオウク', 'town' => '手取本町', 'town_kana' => 'テトリホンチョウ', 'postalcode' => '8600808', 'latitude' => 32.803100, 'longitude' => 130.707890],
        ['prefecture' => '大分県', 'prefecture_kana' => 'オオイタケン', 'city' => '大分市', 'city_kana' => 'オオイタシ', 'town' => '荷揚町', 'town_kana' => 'ニアゲマチ', 'postalcode' => '8700046', 'latitude' => 33.238200, 'longitude' => 131.612600],
        ['prefecture' => '宮崎県', 'prefecture_kana' => 'ミヤザキケン', 'city' => '宮崎市', 'city_kana' => 'ミヤザキシ', 'town' => '橘通西', 'town_kana' => 'タチバナドオリニシ', 'postalcode' => '8800001', 'latitude' => 31.911100, 'longitude' => 131.423900],
        ['prefecture' => '鹿児島県', 'prefecture_kana' => 'カゴシマケン', 'city' => '鹿児島市', 'city_kana' => 'カゴシマシ', 'town' => '山下町', 'town_kana' => 'ヤマシタチョウ', 'postalcode' => '8920816', 'latitude' => 31.560200, 'longitude' => 130.558100],
        ['prefecture' => '沖縄県', 'prefecture_kana' => 'オキナワケン', 'city' => '那覇市', 'city_kana' => 'ナハシ', 'town' => '泉崎', 'town_kana' => 'イズミザキ', 'postalcode' => '9000021', 'latitude' => 26.212400, 'longitude' => 127.680930],
    ];

    /**
     * Add 10,000 Japanese parking spots without removing existing records.
     */
    public function run(): void
    {
        $userIds = User::query()->pluck('id')->all();
        $locationsByPrefecture = $this->locationsByPrefecture();

        if ($userIds === []) {
            throw new LogicException('JapanParkingSpotSeeder requires at least one user. Run DatabaseSeeder or create a user first.');
        }

        if ($locationsByPrefecture === []) {
            throw new LogicException('JapanParkingSpotSeeder requires Japanese address and coordinate data.');
        }

        $now = now();
        $seedPrefix = '国内サンプル駐輪場 '.now()->format('YmdHis').'-'.Str::lower(Str::random(6));
        $prefectureQueue = $this->prefectureQueue(array_keys($locationsByPrefecture));

        for ($offset = 0; $offset < self::PARKING_SPOT_COUNT; $offset += self::INSERT_CHUNK_SIZE) {
            $parkingSpots = [];
            $parkingSpotNames = [];

            for ($position = $offset; $position < min($offset + self::INSERT_CHUNK_SIZE, self::PARKING_SPOT_COUNT); $position++) {
                $locations = $locationsByPrefecture[$prefectureQueue[$position]];
                $location = $locations[array_rand($locations)];
                $name = sprintf(
                    '%s %s%s-%05d',
                    $seedPrefix,
                    $location['city'],
                    self::PARKING_SPOT_NAME_SUFFIXES[array_rand(self::PARKING_SPOT_NAME_SUFFIXES)],
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

        $this->command?->info('Created 10,000 Japanese sample parking spots.');
    }

    /**
     * @return array<string, list<array{postalcode_id: int, prefecture: string, city: string, town: string, latitude: float, longitude: float}>>
     */
    private function locationsByPrefecture(): array
    {
        $locationsByPrefecture = [];

        foreach (self::LOCATIONS as $location) {
            $prefecture = Prefecture::query()->firstOrCreate(
                ['name' => $location['prefecture']],
                ['name_kana' => $location['prefecture_kana']],
            );
            $city = City::query()->firstOrCreate(
                ['prefecture_id' => $prefecture->id, 'name' => $location['city']],
                ['name_kana' => $location['city_kana']],
            );
            $postalcode = Postalcode::query()->firstOrCreate(
                ['postalcode' => $location['postalcode'], 'city_id' => $city->id],
                ['name' => $location['town'], 'name_kana' => $location['town_kana']],
            );

            $locationsByPrefecture[$location['prefecture']][] = [
                'postalcode_id' => $postalcode->id,
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
     * @param  list<string>  $prefectures
     * @return list<string>
     */
    private function prefectureQueue(array $prefectures): array
    {
        $queue = [];

        while (count($queue) < self::PARKING_SPOT_COUNT) {
            $cycle = $prefectures;
            shuffle($cycle);

            foreach ($cycle as $prefecture) {
                $queue[] = $prefecture;
            }
        }

        return array_slice($queue, 0, self::PARKING_SPOT_COUNT);
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

    private function randomizedCoordinate(float $coordinate): float
    {
        return round($coordinate + (random_int(-600, 600) / 100_000), 6);
    }
}
