<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneTemporaryParkingSpotImages extends Command
{
    protected $signature = 'parking-spots:prune-temporary-images
                            {--hours= : Delete temporary images older than this many hours}';

    protected $description = '期限切れの駐輪場確認用一時画像を削除する';

    public function handle(): int
    {
        $hours = filter_var(
            $this->option('hours') ?? config('parking_spot.confirmation.lifetime_hours'),
            FILTER_VALIDATE_INT,
            [
                'options' => ['min_range' => 1],
            ],
        );

        if ($hours === false) {
            $this->error('--hours には1以上の整数を指定してください。');

            return self::INVALID;
        }

        $disk = Storage::disk('public');
        $cutoff = now()->subHours($hours)->getTimestamp();
        $deleted = 0;
        $failed = 0;

        foreach ($disk->allFiles('temp/parking-spots') as $path) {
            try {
                if ($disk->lastModified($path) > $cutoff) {
                    continue;
                }

                if ($disk->delete($path)) {
                    $deleted++;
                } else {
                    $failed++;
                    Log::warning('期限切れの駐輪場一時画像を削除できませんでした。', ['path' => $path]);
                }
            } catch (\Throwable $exception) {
                $failed++;
                Log::warning('期限切れの駐輪場一時画像の確認中に例外が発生しました。', [
                    'path' => $path,
                    'exception' => $exception,
                ]);
            }
        }

        $this->info("期限切れの駐輪場一時画像を{$deleted}件削除しました。");

        if ($failed > 0) {
            $this->warn("{$failed}件の削除に失敗しました。ログを確認してください。");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
