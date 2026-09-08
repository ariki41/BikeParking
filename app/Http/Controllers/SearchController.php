<?php

namespace App\Http\Controllers;

use App\Exceptions\YolpApiException;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $service) {}

    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $engineDisplacement = $request->query('engine_displacement');
        $zoom = $this->normalizeZoom($request->query('zoom'));

        try {
            $yolpLocation = $this->service->getYolpLocation($request);
        } catch (YolpApiException $exception) {
            Log::warning('YOLP API is unavailable while searching.', [
                'category' => $exception->category(),
                'previous_exception' => $exception->getPrevious() ? $exception->getPrevious()::class : null,
            ]);
            session()->flash('error', $exception->userMessage());
            $yolpLocation = [
                'lon' => $request->input('lon') ?? 139.767052,
                'lat' => $request->input('lat') ?? 35.681167,
            ];
        }

        return view('search', compact('keyword', 'engineDisplacement', 'yolpLocation', 'zoom'));
    }

    private function normalizeZoom(mixed $zoom): int
    {
        $normalizedZoom = filter_var($zoom, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => config('parking_spot.search_map.min_zoom'),
                'max_range' => config('parking_spot.search_map.max_zoom'),
            ],
        ]);

        return $normalizedZoom === false
            ? config('parking_spot.search_map.default_zoom')
            : $normalizedZoom;
    }
}
