<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SearchService
{
    public function getNominatimJson($request)
    {
        $keyword = $request->get('keyword');

        try {
            $response = Http::timeout(5)
                ->retry(3, 100)
                ->get(env('NOMINATIM_URL'), [
                    'q' => $keyword,
                    'limit' => 20,
                    'format' => 'json',
                ]);

            $response->throw();

            $nominatim = $response->json();

            usort($nominatim, function ($a, $b) {
                return $b['importance'] <=> $a['importance'];
            });

        } catch (RequestException $e) {
            logger()->error(
                'nominatim HTTP Request Error',
                ['error' => $e->getMessage()]
            );
        } finally {
            if (empty($nominatim)) {
                $nominatim = [0 => ['lat' => '35.68111', 'lon' => '139.76667']]; //　東京駅をデフォルトに設定
            }
        }

        return $nominatim;
    }
}
