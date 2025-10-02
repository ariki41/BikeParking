<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotRequest;
use App\Services\ParkingSpotService;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use Illuminate\Http\Request;

class ParkingSpotController extends Controller
{
    public function __construct()
    {
        $this->service = new ParkingSpotService;
    }

    public function show($id)
    {
        $parkingSpot = ParkingSpot::findOrFail($id);

        $postalcode = Postalcode::getPostalcode($parkingSpot->postalcode)->postalcode;
        $capacity = config('categories.parking_spot_capacity');

        $parkingSpot['postalcode'] = $postalcode;
        $parkingSpot['capacity'] = $capacity[$parkingSpot['capacity']];
        $parkingSpot['opening_time'] = date("H:i", strtotime($parkingSpot['opening_time']));
        $parkingSpot['closing_time'] = $parkingSpot['closing_time'] === '00:00:00' ? '24:00' : date("H:i", strtotime($parkingSpot['closing_time']));

        
        return view('parking_spot.show', compact('parkingSpot'));
        
    }

    public function create()
    {
        $capacity = config('categories.parking_spot_capacity');

        return view('parking_spot.create', compact('capacity'));
    }

    public function confirm(ParkingSpotRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['id'] = $request->input('id');
        $validatedData['address'] = mb_convert_kana($validatedData['address1'].$validatedData['address2'], 'rn');
        $validatedData['postalcode'] = mb_convert_kana(str_replace('-', '', $validatedData['postalcode']), 'rn');

        $yolpLocation = $this->service->getYolpLonLat($validatedData['address']);

        if (is_null($yolpLocation)) {
            return redirect()->route('parking_spot.create')->withErrors(['address2' => '住所が見つかりません。'])->withInput();
        }

        $validatedData['longitude'] = $yolpLocation['lon'];
        $validatedData['latitude'] = $yolpLocation['lat'];
        $validatedData['address'] = $yolpLocation['address'];

        $capacity = config('categories.parking_spot_capacity');

        if ($validatedData['id']) {
            $request->session()->put('edit_parking_spot_form', $validatedData);
        } else {
            $request->session()->put('create_parking_spot_form', $validatedData);
        }

        return view('parking_spot.confirm', compact('validatedData', 'capacity'));
    }

    public function store(Request $request)
    {
        $input = $request->session()->get('create_parking_spot_form');
        $request->session()->forget('create_parking_spot_form');

        if ($request->input('back') === 'back') {
            return redirect()->route('parking_spot.create')->withInput($input);
        }

        $this->service->saveParkingSpot($input);
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '駐車場を登録しました。');
    }

    public function edit($id)
    {
        $parkingSpot = ParkingSpot::findOrFail($id);
        $capacity = config('categories.parking_spot_capacity');
        
        $postalcode = Postalcode::getPostalcode($parkingSpot->postalcode)->postalcode;
        $address1Sql = Postalcode::getAddress($postalcode)->first();
        $address1 = $address1Sql->prefecture.$address1Sql->city.$address1Sql->town;
        $address2 = str_replace($address1, '', $parkingSpot->address);

        // 時間フォーマット変換
        $parkingSpot['opening_time'] = date("H:i", strtotime($parkingSpot['opening_time']));
        $parkingSpot['closing_time'] = date("H:i", strtotime($parkingSpot['closing_time']));

        $session = session('edit_parking_spot_form') ?? [];

        return view('parking_spot.edit', compact('parkingSpot', 'capacity', 'postalcode', 'address1', 'address2', 'session'));
    }

    public function update(Request $request)
    {
        $input = $request->session()->get('edit_parking_spot_form');
        $request->session()->forget('edit_parking_spot_form');
        
        if ($request->input('back') === 'back') {
            return redirect()->route('parking_spot.edit', ['id' => $input['id']])->withInput($input);
        }

        $this->service->updateParkingSpot($input);

        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', '駐車場情報を更新しました。');
    }
}
