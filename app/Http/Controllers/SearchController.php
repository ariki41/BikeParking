<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new SearchService;
    }

    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $nominatim = $this->service->getNominatimJson($request);

        dump($nominatim);

        return view('search', compact('keyword', 'nominatim'));
    }
}
