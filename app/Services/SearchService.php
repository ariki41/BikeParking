<?php

namespace App\Services;

use Illuminate\Http\Request;

class SearchService
{
    public function __construct(private readonly YolpApiClient $client) {}

    /**
     * Yahoo!ローカルサーチAPIを利用してキーワードから緯度経度を取得
     *
     * @return array{lon: string|float, lat: string|float}
     */
    public function getYolpLocation(Request $request): array
    {
        $keyword = $request->get('keyword');
        $location = null;

        if (! is_null($keyword)) {
            $location = $this->client->search((string) $keyword);
        }

        if ($location !== null) {
            session()->forget('error');

            return [
                'lon' => $location['lon'],
                'lat' => $location['lat'],
            ];
        }

        session()->flash('error', '検索結果が見つかりませんでした。');

        return [
            'lon' => $request->input('lon') ?? 139.767052,
            'lat' => $request->input('lat') ?? 35.681167,
        ];
    }
}
