<?php

namespace App\Http\Controllers;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $service) {}

    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $engineDisplacement = EngineDisplacementClass::tryFrom(
            (string) $request->query('engine_displacement'),
        )?->value;
        $displacementClasses = EngineDisplacementClass::cases();

        $yolpLocation = $this->service->getYolpLocation($request);

        return view('search', compact('keyword', 'engineDisplacement', 'displacementClasses', 'yolpLocation'));
    }
}
