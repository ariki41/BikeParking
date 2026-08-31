<?php

namespace App\Console\Commands;

use App\Services\PostalCodeImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ImportPrefCityPostalcode extends Command
{
    protected $signature = 'postal-codes:sync
                            {--url= : 日本郵便の郵便番号データZIP URL}';

    /**
     * @var list<string>
     */
    protected $aliases = ['app:import-pref-city-postalcode'];

    protected $description = '日本郵便から最新の都道府県・市区町村・郵便番号データを取得して同期する';

    public function handle(PostalCodeImportService $importer): int
    {
        $url = $this->option('url') ?: config('postal_codes.download_url');

        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->error('郵便番号データのダウンロードURLが正しくありません。');

            return self::INVALID;
        }

        $lock = Cache::lock('postal-codes:sync', (int) config('postal_codes.lock_seconds'));

        if (! $lock->get()) {
            $this->error('別の郵便番号同期が実行中です。');

            return self::FAILURE;
        }

        try {
            $this->info('日本郵便から郵便番号データをダウンロードしています。');
            $result = $importer->downloadAndImport(
                $url,
                $this->normalizeRecordForAddressSearch(...),
            );

            $this->info(sprintf(
                '郵便番号データを同期しました（CSV: %s件、有効: %s件、無効: %s件）。',
                number_format($result['source_records']),
                number_format($result['active_postalcodes']),
                number_format($result['inactive_postalcodes']),
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('郵便番号データを同期できませんでした: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    /**
     * 日本郵便CSVの人間向け注記を、住所補完で利用できる町域名に変換する。
     *
     * @param  array{
     *     postalcode: string,
     *     prefecture: string,
     *     prefecture_kana: string,
     *     city: string,
     *     city_kana: string,
     *     town: string,
     *     town_kana: string
     * }  $record
     * @return array{
     *     postalcode: string,
     *     prefecture: string,
     *     prefecture_kana: string,
     *     city: string,
     *     city_kana: string,
     *     town: string,
     *     town_kana: string
     * }
     */
    private function normalizeRecordForAddressSearch(array $record): array
    {
        $record['town'] = $this->normalizeTown(
            $record['town'],
            $record['city'],
            false,
        );
        $record['town_kana'] = $record['town'] === ''
            ? ''
            : $this->normalizeTown($record['town_kana'], $record['city_kana'], true);

        return $record;
    }

    private function normalizeTown(string $town, string $city, bool $kana): string
    {
        $notListed = $kana ? 'イカニケイサイガナイバアイ' : '以下に掲載がない場合';
        $followedByStreetNumber = $kana ? 'ノツギニバンチガクルバアイ' : 'の次に番地がくる場合';

        if (str_contains($town, $notListed) || str_contains($town, $followedByStreetNumber)) {
            return '';
        }

        $wideAreaSuffix = $kana ? 'イチエン' : '一円';

        if ($this->isCityWideTown($town, $city, $wideAreaSuffix)) {
            return '';
        }

        $town = str_replace(
            $kana ? '（コウソウトウ）' : '（高層棟）',
            '',
            $town,
        );

        $floorPattern = $kana
            ? '/（[0-9０-９]+カイ）$/u'
            : '/（[0-9０-９]+階）$/u';

        if (preg_match($floorPattern, $town) === 1) {
            return trim(str_replace(['（', '）'], '', $town));
        }

        $town = $this->removeDistrictRange($town, $kana);

        $districtSeparator = $kana ? 'チワリ、' : '地割、';

        if (str_contains($town, $districtSeparator)) {
            $town = explode('、', $town, 2)[0];
        }

        $kouOtsu = $kana ? 'コウ、オツ' : '甲、乙';

        if (str_starts_with($town, $kouOtsu)) {
            $town = explode('、', $town, 2)[0];
        }

        $openParenPosition = mb_strpos($town, '（');

        if ($openParenPosition !== false && mb_strpos($town, '）', $openParenPosition) !== false) {
            $town = mb_substr($town, 0, $openParenPosition);
        }

        return trim($town);
    }

    private function isCityWideTown(string $town, string $city, string $suffix): bool
    {
        if (! str_ends_with($town, $suffix) || $town === $suffix) {
            return false;
        }

        return str_ends_with($city, mb_substr($town, 0, -mb_strlen($suffix)));
    }

    private function removeDistrictRange(string $town, bool $kana): string
    {
        $patterns = $kana
            ? ['/ダイ.*チワリ〜ダイ.*チワリ/u', '/[0-9０-９]+チワリ〜.*/u']
            : ['/第.*地割〜第.*地割/u', '/[0-9０-９]+地割〜.*/u'];

        return preg_replace($patterns, '', $town) ?? $town;
    }
}
