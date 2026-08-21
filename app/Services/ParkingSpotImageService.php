<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\ValueObjects\PersistedParkingSpotImages;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ParkingSpotImageService
{
    private const MAX_FINAL_IMAGE_BYTES = 5 * 1024 * 1024;

    /**
     * @param  list<string>  $currentPaths
     * @param  list<string>  $allowedPermanentPaths
     * @param  list<string>  $allowedTemporaryPaths
     * @return list<string>
     */
    public function prepareForConfirmation(
        Request $request,
        array $currentPaths,
        array $allowedPermanentPaths = [],
        array $allowedTemporaryPaths = [],
    ): array {
        $currentPaths = $this->normalizeImagePaths($currentPaths);
        $uploadedImages = $request->file('images', []);
        $uploadedImages = is_array($uploadedImages) ? array_values($uploadedImages) : [];

        if ($request->hasFile('image')) {
            $uploadedImages[] = $request->file('image');
        }

        if ($uploadedImages === []) {
            foreach ($currentPaths as $path) {
                $allowed = $this->isTemporaryImagePath($path)
                    ? in_array($path, $allowedTemporaryPaths, true)
                    : in_array($path, $allowedPermanentPaths, true);

                if (! $allowed) {
                    throw ValidationException::withMessages([
                        'image_paths' => '保持している画像情報が正しくありません。',
                    ]);
                }
            }

            return $currentPaths;
        }

        $batchId = (string) Str::uuid();
        $tempPaths = [];

        try {
            foreach ($uploadedImages as $position => $uploadedImage) {
                $suffix = $position === 0 ? '' : '_'.($position + 1);
                $tempPath = 'temp/parking-spots/'.$batchId.$suffix.'.webp';
                $webpBinary = $this->convertImageToWebpWithinLimit($uploadedImage->getRealPath());
                if (! Storage::disk('public')->put($tempPath, $webpBinary)) {
                    throw new \RuntimeException("Unable to store temporary parking spot image [{$tempPath}].");
                }

                $tempPaths[] = $tempPath;
            }
        } catch (\Throwable) {
            $this->deleteImagePaths($tempPaths);

            throw ValidationException::withMessages([
                'images' => '画像をWebP形式へ変換できませんでした。別の画像をお試しください。',
            ]);
        }

        $this->deleteImagePaths(array_filter(
            $currentPaths,
            fn (string $path) => $this->isTemporaryImagePath($path),
        ));

        return $tempPaths;
    }

    /**
     * @param  list<string>  $imagePaths
     */
    public function persistConfirmedImages(
        ParkingSpot $parkingSpot,
        array $imagePaths,
    ): PersistedParkingSpotImages {
        $persistedImagePaths = [];
        $createdImagePaths = [];
        $temporaryImagePaths = [];

        try {
            foreach ($this->normalizeImagePaths($imagePaths) as $position => $imagePath) {
                if (! $this->isTemporaryImagePath($imagePath)) {
                    $persistedImagePaths[] = $imagePath;

                    continue;
                }

                $disk = Storage::disk('public');

                if (! $disk->exists($imagePath)) {
                    throw new \RuntimeException("Temporary parking spot image [{$imagePath}] does not exist.");
                }

                $timestamp = CarbonImmutable::now()->format('YmdHisv');
                $suffix = $position === 0 ? '' : '_'.($position + 1);
                $permanentPath = 'parking-spots/'.$parkingSpot->id.'_'.$timestamp.$suffix.'.webp';

                if (! $disk->copy($imagePath, $permanentPath)) {
                    throw new \RuntimeException("Unable to persist parking spot image [{$imagePath}].");
                }

                $persistedImagePaths[] = $permanentPath;
                $createdImagePaths[] = $permanentPath;
                $temporaryImagePaths[] = $imagePath;
            }
        } catch (\Throwable $exception) {
            $this->deleteImagePaths($createdImagePaths);

            throw $exception;
        }

        return new PersistedParkingSpotImages(
            paths: $persistedImagePaths,
            createdPaths: $createdImagePaths,
            temporaryPaths: $temporaryImagePaths,
        );
    }

    /**
     * @param  list<string>  $imagePaths
     */
    public function replaceParkingSpotImages(ParkingSpot $parkingSpot, array $imagePaths): void
    {
        $parkingSpot->images()->delete();

        $parkingSpot->images()->createMany(
            collect($imagePaths)
                ->map(fn (string $path, int $position) => compact('path', 'position'))
                ->all(),
        );

        $parkingSpot->unsetRelation('images');
    }

    /**
     * @param  list<string>  $imagePaths
     */
    public function deleteImagePaths(array $imagePaths): void
    {
        $disk = Storage::disk('public');

        foreach ($this->normalizeImagePaths($imagePaths) as $imagePath) {
            try {
                if ($disk->exists($imagePath) && ! $disk->delete($imagePath)) {
                    Log::warning('駐輪場画像を削除できませんでした。', ['path' => $imagePath]);
                }
            } catch (\Throwable $exception) {
                Log::warning('駐輪場画像の削除中に例外が発生しました。', [
                    'path' => $imagePath,
                    'exception' => $exception,
                ]);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $imagePaths
     * @return list<string>
     */
    private function normalizeImagePaths(array $imagePaths): array
    {
        return collect($imagePaths)
            ->filter(fn ($path) => is_string($path) && filled($path))
            ->unique()
            ->values()
            ->all();
    }

    private function isTemporaryImagePath(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'temp/parking-spots/');
    }

    private function convertImageToWebpWithinLimit(string $sourcePath): string
    {
        $manager = $this->imageManager();
        $quality = 85;
        $maxWidth = null;

        while (true) {
            $image = $manager->decodePath($sourcePath);

            if ($maxWidth !== null && $image->width() > $maxWidth) {
                $image = $image->scaleDown(width: $maxWidth);
            }

            $encoded = $image->encode(new WebpEncoder(quality: $quality));
            $binary = (string) $encoded;

            if (strlen($binary) <= self::MAX_FINAL_IMAGE_BYTES) {
                return $binary;
            }

            if ($quality > 55) {
                $quality -= 10;

                continue;
            }

            $nextWidth = $maxWidth
                ? (int) floor($maxWidth * 0.85)
                : (int) floor($image->width() * 0.85);

            if ($nextWidth < 600 || $nextWidth >= $image->width()) {
                break;
            }

            $maxWidth = $nextWidth;
        }

        throw new \RuntimeException('Unable to convert image within size limit.');
    }

    private function imageManager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return new ImageManager(new ImagickDriver);
        }

        return new ImageManager(new GdDriver);
    }
}
