<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotModerationAction;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParkingSpotModerationService
{
    public function hide(ParkingSpot $parkingSpot, User $actor): void
    {
        DB::transaction(function () use ($parkingSpot, $actor): void {
            $parkingSpot = ParkingSpot::query()->lockForUpdate()->findOrFail($parkingSpot->id);
            if (! $parkingSpot->is_published) {
                return;
            }

            $parkingSpot->is_published = false;
            $parkingSpot->save();
            $this->recordAction($parkingSpot, $actor, 'hidden');
        });
    }

    public function publish(ParkingSpot $parkingSpot, User $actor): void
    {
        DB::transaction(function () use ($parkingSpot, $actor): void {
            $parkingSpot = ParkingSpot::query()->lockForUpdate()->findOrFail($parkingSpot->id);
            if ($parkingSpot->is_published) {
                return;
            }

            $parkingSpot->is_published = true;
            $parkingSpot->save();
            $this->recordAction($parkingSpot, $actor, 'published');
        });
    }

    public function restoreToHistory(ParkingSpot $parkingSpot, ParkingSpotUpdateHistory $target, User $actor): void
    {
        DB::transaction(function () use ($parkingSpot, $target, $actor): void {
            $parkingSpot = ParkingSpot::query()->lockForUpdate()->findOrFail($parkingSpot->id);
            $histories = $parkingSpot->updateHistories()
                // 履歴は差分なので、指定時点より後の変更を新しい順に打ち消して復元する。
                ->where(function ($query) use ($target): void {
                    $query->where('created_at', '>', $target->created_at)
                        ->orWhere(function ($query) use ($target): void {
                            $query->where('created_at', $target->created_at)
                                ->where('id', '>', $target->id);
                        });
                })
                ->get();
            foreach ($histories as $history) {
                foreach ($history->getAttribute('changes') ?? [] as $field => $change) {
                    if ($field !== 'rates' && $field !== 'images' && array_key_exists('before', $change)) {
                        $parkingSpot->setAttribute($field, $change['before']);
                    }
                    if ($field === 'rates' && array_key_exists('before', $change)) {
                        $this->replaceRates($parkingSpot, $change['before']);
                    }
                }
            }
            $parkingSpot->save();
            $this->recordAction($parkingSpot, $actor, 'restored', $target);
        });
    }

    private function replaceRates(ParkingSpot $parkingSpot, array $rates): void
    {
        $parkingSpot->rates()->delete();
        foreach ($rates as $rate) {
            ParkingSpotRates::create([
                'parking_spot_id' => $parkingSpot->id,
                ...$rate,
            ]);
        }
    }

    private function recordAction(ParkingSpot $parkingSpot, User $actor, string $action, ?ParkingSpotUpdateHistory $history = null): void
    {
        ParkingSpotModerationAction::create([
            'parking_spot_id' => $parkingSpot->id,
            'parking_spot_update_history_id' => $history?->id,
            'user_id' => $actor->id,
            'action' => $action,
            'details' => $history ? ['restored_to' => $history->created_at?->toIso8601String()] : null,
        ]);
    }
}
