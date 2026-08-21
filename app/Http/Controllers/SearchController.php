<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $service) {}

    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $yolpLocation = $this->service->getYolpLocation($request);

        return view('search', compact('keyword', 'yolpLocation'));
    }
}
