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
