<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Postalcode;
use App\Models\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;
use ZipArchive;

class PostalCodeSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    private const DOWNLOAD_URL = 'https://postal-code.test/utf_ken_all.zip';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('postal_codes.download_url', self::DOWNLOAD_URL);
        config()->set('postal_codes.minimum_records', 2);
        config()->set('postal_codes.minimum_prefectures', 2);
    }

    public function test_it_downloads_and_idempotently_syncs_the_official_postal_code_data(): void
    {
        $tokyo = Prefecture::query()->create([
            'name' => '東京都',
            'name_kana' => 'フルイトウキョウト',
        ]);
        $chiyoda = City::query()->create([
            'prefecture_id' => $tokyo->id,
            'name' => '千代田区',
            'name_kana' => 'フルイチヨダク',
        ]);
        $existing = Postalcode::query()->create([
            'postalcode' => '1000001',
            'city_id' => $chiyoda->id,
            'name' => '旧町域',
            'name_kana' => 'キュウチョウイキ',
        ]);
        $obsolete = Postalcode::query()->create([
            'postalcode' => '9999999',
            'city_id' => $chiyoda->id,
            'name' => '廃止町域',
            'name_kana' => 'ハイシチョウイキ',
        ]);

        Http::fake([
            self::DOWNLOAD_URL => Http::response($this->postalCodeZip([
                $this->csvRow('01101', '0600000', 'ホッカイドウ', 'サッポロシチュウオウク', 'イカニケイサイガナイバアイ', '北海道', '札幌市中央区', '以下に掲載がない場合'),
                $this->csvRow('13101', '1000001', 'トウキョウト', 'チヨダク', 'チヨダ', '東京都', '千代田区', '千代田'),
                // The first official row is retained when a postal code covers multiple towns.
                $this->csvRow('13101', '1000001', 'トウキョウト', 'チヨダク', 'マルノウチ', '東京都', '千代田区', '丸の内'),
            ]), 200, ['Content-Type' => 'application/zip']),
        ]);

        $this->artisan('postal-codes:sync')
            ->expectsOutputToContain('CSV: 3件、有効: 2件、無効: 1件')
            ->assertSuccessful();

        $this->assertDatabaseHas('prefectures', [
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $this->assertDatabaseHas('prefectures', [
            'name' => '海外',
            'name_kana' => 'カイガイ',
        ]);
        $this->assertDatabaseHas('cities', [
            'id' => $chiyoda->id,
            'name_kana' => 'チヨダク',
        ]);
        $this->assertDatabaseHas('postalcodes', [
            'postalcode' => '0600000',
            'name' => '',
            'name_kana' => '',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('postalcodes', [
            'id' => $existing->id,
            'postalcode' => '1000001',
            'name' => '千代田',
            'name_kana' => 'チヨダ',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('postalcodes', [
            'id' => $obsolete->id,
            'is_active' => false,
        ]);

        $this->artisan('postal-codes:sync')->assertSuccessful();

        $this->assertDatabaseCount('postalcodes', 3);
        $this->assertSame(2, Postalcode::query()->active()->count());
        Http::assertSentCount(2);
    }

    public function test_it_normalizes_official_town_notes_for_address_search(): void
    {
        Http::fake([
            self::DOWNLOAD_URL => Http::response($this->postalCodeZip([
                $this->csvRow('20482', '3998501', 'ナガノケン', 'キタアヅミグンマツカワムラ', 'マツカワムライチエン', '長野県', '北安曇郡松川村', '松川村一円'),
                $this->csvRow('01649', '0881646', 'ホッカイドウ', 'トカチグンウラホロチョウ', 'アツナイ（ゼンイキ）', '北海道', '十勝郡浦幌町', '厚内（全域）'),
                $this->csvRow('13113', '1506147', 'トウキョウト', 'シブヤク', 'シブヤシブヤスクランブルスクエア（４７カイ）', '東京都', '渋谷区', '渋谷渋谷スクランブルスクエア（４７階）'),
                $this->csvRow('01407', '0482402', 'ホッカイドウ', 'ヨイチグンニキチョウ', 'オオエ（２チョウメ６５１バンチイガイ）', '北海道', '余市郡仁木町', '大江（２丁目６５１番地以外）'),
                $this->csvRow('03366', '0295523', 'イワテケン', 'ワガグンニシワガマチ', 'エッチュウハタ６４チワリ〜エッチュウハタ６６チワリ', '岩手県', '和賀郡西和賀町', '越中畑６４地割〜越中畑６６地割'),
                $this->csvRow('03366', '0295503', 'イワテケン', 'ワガグンニシワガマチ', 'アナアケ２２チワリ、アナアケ２３チワリ', '岩手県', '和賀郡西和賀町', '穴明２２地割、穴明２３地割'),
                $this->csvRow('37322', '7614103', 'カガワケン', 'ショウズグントノショウチョウ', 'コウ、オツ（オオキド）', '香川県', '小豆郡土庄町', '甲、乙（大木戸）'),
            ])),
        ]);

        $this->artisan('postal-codes:sync')
            ->expectsOutputToContain('CSV: 7件、有効: 7件')
            ->assertSuccessful();

        $this->assertNormalizedTown('3998501', '', '');
        $this->assertNormalizedTown('0881646', '厚内', 'アツナイ');
        $this->assertNormalizedTown('1506147', '渋谷渋谷スクランブルスクエア４７階', 'シブヤシブヤスクランブルスクエア４７カイ');
        $this->assertNormalizedTown('0482402', '大江', 'オオエ');
        $this->assertNormalizedTown('0295523', '越中畑', 'エッチュウハタ');
        $this->assertNormalizedTown('0295503', '穴明２２地割', 'アナアケ２２チワリ');
        $this->assertNormalizedTown('7614103', '甲', 'コウ');
    }

    public function test_it_does_not_change_the_database_when_the_download_is_not_a_zip(): void
    {
        $postalcode = $this->createExistingPostalcode();

        Http::fake([
            self::DOWNLOAD_URL => Http::response('<html>maintenance</html>'),
        ]);

        $this->artisan('postal-codes:sync')
            ->expectsOutputToContain('有効なZIPではありません')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseHas('postalcodes', [
            'id' => $postalcode->id,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_an_incomplete_csv_before_changing_the_database(): void
    {
        $postalcode = $this->createExistingPostalcode();

        Http::fake([
            self::DOWNLOAD_URL => Http::response($this->postalCodeZip([
                $this->csvRow('13101', '1000001', 'トウキョウト', 'チヨダク', 'チヨダ', '東京都', '千代田区', '千代田'),
            ])),
        ]);

        $this->artisan('postal-codes:sync')
            ->expectsOutputToContain('有効レコード数が少なすぎます')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseHas('postalcodes', [
            'id' => $postalcode->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('prefectures', 1);
    }

    private function createExistingPostalcode(): Postalcode
    {
        $prefecture = Prefecture::query()->create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $city = City::query()->create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);

        return Postalcode::query()->create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
    }

    private function assertNormalizedTown(string $postalcode, string $name, string $nameKana): void
    {
        $this->assertDatabaseHas('postalcodes', [
            'postalcode' => $postalcode,
            'name' => $name,
            'name_kana' => $nameKana,
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $rows
     */
    private function postalCodeZip(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'postal-code-test-');
        $this->assertNotFalse($path);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $this->assertTrue($archive->addFromString('utf_ken_all.csv', implode("\r\n", $rows)."\r\n"));
        $this->assertTrue($archive->close());

        try {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents);

            return $contents;
        } finally {
            @unlink($path);
        }
    }

    private function csvRow(
        string $municipalityCode,
        string $postalcode,
        string $prefectureKana,
        string $cityKana,
        string $townKana,
        string $prefecture,
        string $city,
        string $town,
    ): string {
        return implode(',', [
            $municipalityCode,
            '"000  "',
            '"'.$postalcode.'"',
            '"'.$prefectureKana.'"',
            '"'.$cityKana.'"',
            '"'.$townKana.'"',
            '"'.$prefecture.'"',
            '"'.$city.'"',
            '"'.$town.'"',
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
        ]);
    }
}
