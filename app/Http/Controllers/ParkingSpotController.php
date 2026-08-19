<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotRequest;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Services\ParkingSpotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ParkingSpotController extends Controller
{
    public function __construct()
    {
        $this->service = new ParkingSpotService;
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = ParkingSpot::with(['images', 'rates', 'reviews.user', 'updateHistories.user'])
            ->withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating');

        if ($user) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $parkingSpot = $query->findOrFail($id);

        $postalcode = Postalcode::getPostalcode($parkingSpot->postalcode)->postalcode;
        $userReview = $user
            ? $parkingSpot->reviews->firstWhere('user_id', $user->id)
            : null;

        $parkingSpot['postalcode'] = $postalcode;
        $parkingSpot['opening_time'] = date('H:i', strtotime($parkingSpot['opening_time']));
        $parkingSpot['closing_time'] = $parkingSpot['closing_time'] === '00:00:00' ? '24:00' : date('H:i', strtotime($parkingSpot['closing_time']));

        return view('parking_spot.show', compact('parkingSpot', 'userReview'));

    }

    public function create()
    {
        $capacity = config('categories.parking_spot_capacity');
        $rateDayTypes = config('categories.parking_spot_rate_day_types');
        $rateUnitMinutes = config('categories.parking_spot_rate_unit_minutes');
        $ratesInput = old('rates', [$this->defaultRateInput()]);
        $imagePaths = old('image_paths', array_values(array_filter([old('image_path')])));

        return view('parking_spot.create', compact('capacity', 'rateDayTypes', 'rateUnitMinutes', 'ratesInput', 'imagePaths'));
    }

    public function confirm(ParkingSpotRequest $request)
    {
        $validatedData = $request->validated();
        unset($validatedData['image'], $validatedData['images']);
        $validatedData['id'] = $request->input('id');
        $parkingSpot = null;

        if ($validatedData['id']) {
            $parkingSpot = ParkingSpot::with('images')->findOrFail($validatedData['id']);
            Gate::authorize('update', $parkingSpot);
        }

        $currentImagePaths = $validatedData['image_paths']
            ?? array_values(array_filter([$validatedData['image_path'] ?? null]));
        $validatedData['image_paths'] = $this->service->prepareImagePathsForConfirm(
            $request,
            $currentImagePaths,
            $parkingSpot?->image_paths ?? [],
        );
        $validatedData['image_path'] = $validatedData['image_paths'][0] ?? null;
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
        $parkingSpot = ParkingSpot::with(['images', 'rates'])->findOrFail($id);
        Gate::authorize('update', $parkingSpot);

        $capacity = config('categories.parking_spot_capacity');
        $rateDayTypes = config('categories.parking_spot_rate_day_types');
        $rateUnitMinutes = config('categories.parking_spot_rate_unit_minutes');

        $postalcode = Postalcode::getPostalcode($parkingSpot->postalcode)->postalcode;
        $address1Sql = Postalcode::getAddress($postalcode)->first();
        $address1 = $address1Sql->prefecture.$address1Sql->city.$address1Sql->town;
        $address2 = str_replace($address1, '', $parkingSpot->address);

        // 時間フォーマット変換
        $parkingSpot['opening_time'] = date('H:i', strtotime($parkingSpot['opening_time']));
        $parkingSpot['closing_time'] = date('H:i', strtotime($parkingSpot['closing_time']));

        $session = session('edit_parking_spot_form') ?? [];
        $ratesInput = old('rates', $parkingSpot->rates->map(fn ($rate) => [
            'day_type' => $rate->day_type,
            'start_time' => date('H:i', strtotime($rate->start_time)),
            'end_time' => date('H:i', strtotime($rate->end_time)),
            'unit_minutes' => $rate->unit_minutes,
            'rate' => $rate->rate,
            'free_minutes' => $rate->free_minutes,
            'no_free_minutes' => $rate->free_minutes === 0 ? '1' : '0',
            'max_rate' => $rate->max_rate,
            'no_max_rate' => $rate->max_rate === null ? '1' : '0',
        ])->values()->all() ?: [$this->defaultRateInput()]);
        $imagePaths = old('image_paths');
        if (! is_array($imagePaths)) {
            $imagePaths = filled(old('image_path')) ? [old('image_path')] : $parkingSpot->image_paths;
        }

        return view('parking_spot.edit', compact('parkingSpot', 'capacity', 'rateDayTypes', 'rateUnitMinutes', 'postalcode', 'address1', 'address2', 'session', 'ratesInput', 'imagePaths'));
    }

    public function update(Request $request)
    {
        $input = $request->session()->get('edit_parking_spot_form');

        abort_unless(is_array($input) && isset($input['id']), 419);

        $parkingSpot = ParkingSpot::findOrFail($input['id']);
        Gate::authorize('update', $parkingSpot);

        $request->session()->forget('edit_parking_spot_form');

        if ($request->input('back') === 'back') {
            return redirect()->route('parking_spot.edit', ['id' => $input['id']])->withInput($input);
        }

        $this->service->updateParkingSpot($input, $request->user());

        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '駐車場情報を更新しました。');
    }

    private function defaultRateInput(): array
    {
        return [
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => '',
            'free_minutes' => 0,
            'no_free_minutes' => '1',
            'max_rate' => '',
            'no_max_rate' => '0',
        ];
    }
}
