<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotReportRequest;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParkingSpotReportController extends Controller
{
    public function create(ParkingSpot $parkingSpot): View
    {
        $parkingSpot->load('updateHistories');

        return view('parking_spot.report', compact('parkingSpot'));
    }

    public function store(ParkingSpotReportRequest $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        ParkingSpotReport::create(['parking_spot_id' => $parkingSpot->id, 'parking_spot_update_history_id' => $request->validated('parking_spot_update_history_id'), 'user_id' => $request->user()->id, 'reason' => $request->validated('reason')]);

        return redirect()->route('parking_spot.show', $parkingSpot)
            ->with('report_success', '通報を受け付けました。');
    }
}
