<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SearchService
{
    /**
     * Yahoo!ローカルサーチAPIを利用してキーワードから緯度経度を取得
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function getYolpLocation($request)
    {
        $keyword = $request->get('keyword');

        if (!is_null($keyword)) {
            try {
                // Yahoo!ローカルサーチAPIにリクエスト
                $response = Http::timeout(5)
                    ->retry(3, 100)
                    ->get(env('YOLP_URL'),
                        [
                            'appid' => env('YOLP_CLIENT_ID'),
                            'query' => $keyword,
                            'sort' => 'hybrid',
                            'ac' => 'JP',
                            'results' => 1,
                            'detail' => 'simple',
                            'output' => 'json',
                        ])
                    ->throw();

                // レスポンスをJSON形式で取得
                $yolp = $response->json();

                // リクエストエラー時の例外処理
            } catch (RequestException $e) {
                throw new RequestException($e->response, 'YOLP API Error');
            }
        }

        // レスポンスデータから緯度経度を取得
        if (isset($yolp['Feature'][0])) {
            [$lon, $lat] = explode(',', $yolp['Feature'][0]['Geometry']['Coordinates']);
            $yolpLocation = ['lon' => $lon, 'lat' => $lat];

            session()->forget('error');
        } else {
            // レスポンスデータが存在しない場合はGETパラメータを確認
            // GETパラメータが存在する場合はその値を初期値として存在しなければ東京駅を設定
            $yolpLocation = ['lon' => $request->input('lon') ?? 139.767052, 'lat' => $request->input('lat') ?? 35.681167];

            session()->flash('error', '検索結果が見つかりませんでした。');
        }

        return $yolpLocation;
    }
}
