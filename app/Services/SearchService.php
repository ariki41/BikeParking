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

        // キーワードがない共有URLだけ、保存済みの地図位置を再現する。
        if (! filled($keyword) && $this->hasValidRequestedCoordinates($request)) {
            session()->forget('error');

            return [
                'lon' => $request->query('lon'),
                'lat' => $request->query('lat'),
            ];
        }

        $location = null;

        if (filled($keyword)) {
            $location = $this->client->search((string) $keyword);
        }

        if ($location !== null) {
            session()->forget('error');

            return [
                'lon' => $location['lon'],
                'lat' => $location['lat'],
            ];
        }

        if (filled($keyword)) {
            session()->flash('error', '検索結果が見つかりませんでした。');
        } else {
            session()->forget('error');
        }

        // 検索語も位置指定もない初期表示では、地図の開始地点を東京駅にする。
        return [
            'lon' => $request->input('lon') ?? 139.767052,
            'lat' => $request->input('lat') ?? 35.681167,
        ];
    }

    private function hasValidRequestedCoordinates(Request $request): bool
    {
        $latitude = $request->query('lat');
        $longitude = $request->query('lon');

        return is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90
            && (float) $longitude >= -180
            && (float) $longitude <= 180;
    }
}
