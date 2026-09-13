<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotDeletionRequestStoreRequest;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotDeletionRequest;
use App\Services\ParkingSpotModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ParkingSpotLifecycleController extends Controller
{
    public function __construct(private readonly ParkingSpotModerationService $moderation) {}

    public function close(Request $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        Gate::authorize('update', $parkingSpot);
        $this->moderation->hide($parkingSpot, $request->user(), '編集画面から閉鎖済みとして掲載停止');

        return redirect()->route('home')->with('success', '駐輪場を閉鎖済みとして掲載停止にしました。');
    }

    public function requestDeletion(ParkingSpotDeletionRequestStoreRequest $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        Gate::authorize('update', $parkingSpot);

        ParkingSpotDeletionRequest::create([
            'parking_spot_id' => $parkingSpot->id,
            'parking_spot_name' => $parkingSpot->name,
            'type' => 'incorrect_registration',
            'reason' => $request->validated('reason'),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('parking_spot.edit', $parkingSpot)
            ->with('status', '誤登録の削除申請を受け付けました。管理者の確認後に削除されます。');
    }
}
