<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParkingSpotConfirmationService
{
    public const SESSION_KEY = 'parking_spot_confirmation';

    public const MODE_CREATE = 'create';

    public const MODE_EDIT = 'edit';

    public function beginCreate(Request $request): void
    {
        $this->begin($request, self::MODE_CREATE, null);
    }

    public function beginEdit(Request $request, int $parkingSpotId): void
    {
        $this->begin($request, self::MODE_EDIT, $parkingSpotId);
    }

    public function hasState(Request $request): bool
    {
        return is_array($request->session()->get(self::SESSION_KEY));
    }

    public function matches(Request $request, string $mode, ?int $parkingSpotId): bool
    {
        $state = $this->state($request);

        if ($state !== null && $this->isExpired($state)) {
            $this->discard($request);

            return false;
        }

        return $state !== null
            && ($state['mode'] ?? null) === $mode
            && ($state['parking_spot_id'] ?? null) === $parkingSpotId;
    }

    /**
     * @return array<int, string>
     */
    public function allowedTemporaryImagePaths(Request $request, string $mode, ?int $parkingSpotId): array
    {
        if (! $this->matches($request, $mode, $parkingSpotId)) {
            return [];
        }

        return $this->temporaryImagePaths($this->state($request)['temporary_image_paths'] ?? []);
    }

    /**
     * Record temporary images as soon as they are created so a later error or redirect
     * does not detach them from the current browser session.
     *
     * @param  array<int, string>  $imagePaths
     */
    public function trackTemporaryImagePaths(
        Request $request,
        string $mode,
        ?int $parkingSpotId,
        array $imagePaths,
    ): void {
        $state = $this->state($request);

        if ($state === null || ! $this->matches($request, $mode, $parkingSpotId)) {
            throw new \LogicException('駐輪場確認セッションのコンテキストが一致しません。');
        }

        $previousPaths = $this->temporaryImagePaths($state['temporary_image_paths'] ?? []);
        $temporaryImagePaths = $this->temporaryImagePaths($imagePaths);

        $this->deleteTemporaryImagePaths(array_values(array_diff($previousPaths, $temporaryImagePaths)));

        $state['temporary_image_paths'] = $temporaryImagePaths;
        $request->session()->put(self::SESSION_KEY, $state);
    }

    public function confirm(
        Request $request,
        string $mode,
        ?int $parkingSpotId,
        array $input,
    ): void {
        if (! $this->matches($request, $mode, $parkingSpotId)) {
            throw new \LogicException('駐輪場確認セッションのコンテキストが一致しません。');
        }

        $state = $this->state($request);
        $state['input'] = $input;
        $state['temporary_image_paths'] = $this->temporaryImagePaths(
            $input['image_paths'] ?? array_values(array_filter([$input['image_path'] ?? null])),
        );

        $request->session()->put(self::SESSION_KEY, $state);
    }

    public function confirmedInput(Request $request, string $mode): ?array
    {
        $state = $this->state($request);
        $input = $state['input'] ?? null;

        if ($state === null
            || ! $this->matches($request, $mode, $state['parking_spot_id'] ?? null)
            || ! is_array($input)) {
            return null;
        }

        if ($mode === self::MODE_CREATE && ($state['parking_spot_id'] ?? null) !== null) {
            return null;
        }

        if ($mode === self::MODE_EDIT) {
            $parkingSpotId = $state['parking_spot_id'] ?? null;

            if (! is_int($parkingSpotId) || (int) ($input['id'] ?? 0) !== $parkingSpotId) {
                return null;
            }
        }

        return $input;
    }

    public function trustedParkingSpotId(Request $request): ?int
    {
        $state = $this->state($request);

        if ($state !== null && $this->isExpired($state)) {
            $this->discard($request);

            return null;
        }

        $parkingSpotId = $state['parking_spot_id'] ?? null;

        return ($state['mode'] ?? null) === self::MODE_EDIT && is_int($parkingSpotId)
            ? $parkingSpotId
            : null;
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    private function begin(Request $request, string $mode, ?int $parkingSpotId): void
    {
        if ($this->matches($request, $mode, $parkingSpotId)) {
            return;
        }

        $this->discard($request);

        $request->session()->put(self::SESSION_KEY, [
            'mode' => $mode,
            'parking_spot_id' => $parkingSpotId,
            'input' => null,
            'temporary_image_paths' => [],
            'expires_at' => now()->addHours(config('parking_spot.confirmation.lifetime_hours'))->getTimestamp(),
        ]);
    }

    private function discard(Request $request): void
    {
        $state = $this->state($request);

        if ($state !== null) {
            $this->deleteTemporaryImagePaths($state['temporary_image_paths'] ?? []);
        }

        $this->forget($request);
    }

    private function state(Request $request): ?array
    {
        $state = $request->session()->get(self::SESSION_KEY);

        return is_array($state) ? $state : null;
    }

    private function isExpired(array $state): bool
    {
        $expiresAt = $state['expires_at'] ?? null;

        return ! is_int($expiresAt) || $expiresAt <= now()->getTimestamp();
    }

    /**
     * @return array<int, string>
     */
    private function temporaryImagePaths(array $imagePaths): array
    {
        return collect($imagePaths)
            ->filter(fn ($path) => is_string($path) && str_starts_with($path, 'temp/parking-spots/'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $imagePaths
     */
    private function deleteTemporaryImagePaths(array $imagePaths): void
    {
        $disk = Storage::disk('public');

        foreach ($this->temporaryImagePaths($imagePaths) as $imagePath) {
            try {
                if ($disk->exists($imagePath) && ! $disk->delete($imagePath)) {
                    Log::warning('確認セッションの一時画像を削除できませんでした。', ['path' => $imagePath]);
                }
            } catch (\Throwable $exception) {
                Log::warning('確認セッションの一時画像削除中に例外が発生しました。', [
                    'path' => $imagePath,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
