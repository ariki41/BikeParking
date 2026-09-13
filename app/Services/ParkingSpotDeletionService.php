<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParkingSpotDeletionService
{
    public function __construct(private readonly ParkingSpotImageService $images) {}

    public function delete(ParkingSpotDeletionRequest $request, User $actor, string $reason): void
    {
        $imagePaths = [];

        DB::transaction(function () use ($request, $actor, $reason, &$imagePaths): void {
            $request = ParkingSpotDeletionRequest::query()->lockForUpdate()->findOrFail($request->id);
            $parkingSpot = ParkingSpot::query()->with('images')->lockForUpdate()->findOrFail($request->parking_spot_id);

            $imagePaths = $parkingSpot->image_paths;
            $request->update([
                'status' => 'deleted',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'resolution_reason' => $reason,
            ]);

            // 物理削除では関連レコードを先に消し、古い外部キー制約でも整合性を保つ。
            $parkingSpot->rates()->delete();
            $parkingSpot->images()->delete();
            $parkingSpot->favorites()->delete();
            $parkingSpot->reviews()->delete();
            $parkingSpot->reports()->delete();
            DB::table('parking_spot_tags')->where('parking_spot_id', $parkingSpot->id)->delete();
            DB::table('parking_spot_moderation_actions')->where('parking_spot_id', $parkingSpot->id)->delete();
            $parkingSpot->updateHistories()->delete();
            $parkingSpot->delete();
        });

        // DBコミット後に画像を削除し、ファイル削除失敗でデータベース操作を巻き戻さない。
        $this->images->deleteImagePaths($imagePaths);
    }
}
