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
            $result = $importer->downloadAndImport($url);

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
}
