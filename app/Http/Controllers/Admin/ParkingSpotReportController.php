<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParkingSpotModerationRequest;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotDeletionRequest;
use App\Models\ParkingSpotReport;
use App\Models\ParkingSpotUpdateHistory;
use App\Services\ParkingSpotDeletionService;
use App\Services\ParkingSpotModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ParkingSpotReportController extends Controller
{
    public function __construct(
        private readonly ParkingSpotModerationService $moderation,
        private readonly ParkingSpotDeletionService $deletion,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        $reports = ParkingSpotReport::with(['parkingSpot', 'updateHistory', 'reporter', 'reviewer'])->latest()->paginate(20);
        $deletionRequests = ParkingSpotDeletionRequest::with(['parkingSpot', 'requester', 'reviewer'])->latest()->paginate(20, ['*'], 'deletion_page');

        return view('admin.parking_spot_reports.index', compact('reports', 'deletionRequests'));
    }

    public function hide(ParkingSpotModerationRequest $request, int $parkingSpot): RedirectResponse
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        $parkingSpot = ParkingSpot::query()->findOrFail($parkingSpot);
        $this->moderation->hide($parkingSpot, $request->user(), $request->validated('moderation_reason'));
        $this->completeRelatedReports($parkingSpot, $request);

        return back()->with('status', '駐輪場を非公開にしました。');
    }

    public function restore(ParkingSpotModerationRequest $request, int $parkingSpot, ParkingSpotUpdateHistory $history): RedirectResponse
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        $parkingSpot = ParkingSpot::query()->findOrFail($parkingSpot);
        abort_unless($history->parking_spot_id === $parkingSpot->id, 404);
        $this->moderation->restoreToHistory($parkingSpot, $history, $request->user(), $request->validated('moderation_reason'));
        $this->completeRelatedReports($parkingSpot, $request);

        return back()->with('status', '指定した更新時点へ基本情報・料金を差し戻しました。画像は変更していません。');
    }

    public function publish(ParkingSpotModerationRequest $request, int $parkingSpot): RedirectResponse
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        $parkingSpot = ParkingSpot::query()->findOrFail($parkingSpot);
        $this->moderation->publish($parkingSpot, $request->user(), $request->validated('moderation_reason'));

        return back()->with('status', '駐輪場を公開しました。');
    }

    public function resolve(Request $request, ParkingSpotReport $report): RedirectResponse
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        $report->update(['status' => 'resolved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return back()->with('status', '通報を対応済みにしました。');
    }

    public function delete(ParkingSpotModerationRequest $request, ParkingSpotDeletionRequest $deletionRequest): RedirectResponse
    {
        Gate::authorize('viewAdmin', ParkingSpotReport::class);
        abort_unless($deletionRequest->status === 'pending' && $deletionRequest->parking_spot_id !== null, 404);

        $this->deletion->delete($deletionRequest, $request->user(), $request->validated('moderation_reason'));

        return back()->with('status', '誤登録の駐輪場を削除しました。');
    }

    private function completeRelatedReports(ParkingSpot $parkingSpot, Request $request): void
    {
        $parkingSpot->reports()->where('status', 'pending')->update(['status' => 'resolved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
    }
}
