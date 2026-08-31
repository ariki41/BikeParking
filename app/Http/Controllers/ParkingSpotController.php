<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotRequest;
use App\Models\ParkingSpot;
use App\Services\ParkingSpotConfirmationService;
use App\Services\ParkingSpotGeocodingService;
use App\Services\ParkingSpotImageService;
use App\Services\ParkingSpotPersistenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ParkingSpotController extends Controller
{
    public function __construct(
        private readonly ParkingSpotPersistenceService $persistence,
        private readonly ParkingSpotGeocodingService $geocoding,
        private readonly ParkingSpotImageService $images,
        private readonly ParkingSpotConfirmationService $confirmation,
    ) {}

    public function show(Request $request, ParkingSpot $parkingSpot)
    {
        $user = $request->user();
        $parkingSpot
            ->load(['postalcode.city.prefecture', 'images', 'rates', 'updateHistories.user'])
            ->loadCount(['favorites', 'reviews'])
            ->loadAvg('reviews', 'rating');

        $recentReviews = $parkingSpot->reviews()
            ->with('user')
            ->limit(10)
            ->get();

        if ($user) {
            $parkingSpot->loadExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $userReview = $user
            ? $parkingSpot->reviews()->where('user_id', $user->id)->first()
            : null;

        $parkingSpot['opening_time'] = date('H:i', strtotime($parkingSpot['opening_time']));
        $parkingSpot['closing_time'] = $parkingSpot['closing_time'] === '00:00:00' ? '24:00' : date('H:i', strtotime($parkingSpot['closing_time']));

        return view('parking_spot.show', compact('parkingSpot', 'recentReviews', 'userReview'));

    }

    public function create(Request $request)
    {
        $this->confirmation->beginCreate($request);

        $capacity = config('categories.parking_spot_capacity');
        $rateDayTypes = config('categories.parking_spot_rate_day_types');
        $rateUnitMinutes = config('categories.parking_spot_rate_unit_minutes');
        $formValues = [
            'name' => '',
            'postalcode' => '',
            'address1' => '',
            'address2' => '',
            'capacity' => '',
            'opening_time' => '00:00',
            'closing_time' => '00:00',
        ];
        $ratesInput = [$this->defaultRateInput()];
        $imagePaths = [];

        return view('parking_spot.create', compact('capacity', 'rateDayTypes', 'rateUnitMinutes', 'formValues', 'ratesInput', 'imagePaths'));
    }

    public function confirm(ParkingSpotRequest $request)
    {
        $validatedData = $request->validated();
        unset($validatedData['image'], $validatedData['images']);
        $validatedData['id'] = isset($validatedData['id']) ? (int) $validatedData['id'] : null;
        $mode = $validatedData['id']
            ? ParkingSpotConfirmationService::MODE_EDIT
            : ParkingSpotConfirmationService::MODE_CREATE;
        $parkingSpot = null;

        if ($mode === ParkingSpotConfirmationService::MODE_CREATE && ! $this->confirmation->hasState($request)) {
            $this->confirmation->beginCreate($request);
        }

        if (! $this->confirmation->matches($request, $mode, $validatedData['id'])) {
            return $this->redirectToTrustedForm($request)
                ->withErrors(['confirmation' => '入力内容を確認できませんでした。入力画面からやり直してください。']);
        }

        if ($validatedData['id']) {
            $parkingSpot = ParkingSpot::with('images')->findOrFail($validatedData['id']);
            Gate::authorize('update', $parkingSpot);
        }

        $currentImagePaths = $validatedData['image_paths']
            ?? array_values(array_filter([$validatedData['image_path'] ?? null]));
        $validatedData['image_paths'] = $this->images->prepareForConfirmation(
            $request,
            $currentImagePaths,
            $parkingSpot?->image_paths ?? [],
            $this->confirmation->allowedTemporaryImagePaths($request, $mode, $validatedData['id']),
        );
        $validatedData['image_path'] = $validatedData['image_paths'][0] ?? null;
        $this->confirmation->trackTemporaryImagePaths(
            $request,
            $mode,
            $validatedData['id'],
            $validatedData['image_paths'],
        );
        $validatedData['address'] = mb_convert_kana($validatedData['address1'].$validatedData['address2'], 'rn');
        $validatedData['postalcode'] = mb_convert_kana(str_replace('-', '', $validatedData['postalcode']), 'rn');

        $yolpLocation = $this->geocoding->geocode($validatedData['address']);

        if (is_null($yolpLocation)) {
            return $this->redirectToTrustedForm($request)
                ->withErrors(['address2' => '住所が見つかりません。'])
                ->withInput($validatedData);
        }

        $validatedData['longitude'] = $yolpLocation['lon'];
        $validatedData['latitude'] = $yolpLocation['lat'];
        $validatedData['address'] = $yolpLocation['address'];

        $capacity = config('categories.parking_spot_capacity');

        $this->confirmation->confirm($request, $mode, $validatedData['id'], $validatedData);

        return view('parking_spot.confirm', compact('validatedData', 'capacity'));
    }

    public function store(Request $request)
    {
        $input = $this->confirmation->confirmedInput($request, ParkingSpotConfirmationService::MODE_CREATE);

        if ($input === null) {
            return redirect()->route('parking_spot.create')
                ->withErrors(['confirmation' => '確認情報の有効期限が切れました。入力内容を確認して、もう一度お試しください。']);
        }

        if ($request->input('back') === 'back') {
            return redirect()->route('parking_spot.create')->withInput($input);
        }

        try {
            $this->persistence->create($input, $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('parking_spot.create')
                ->withErrors($exception->errors())
                ->withInput($input);
        }

        $this->confirmation->forget($request);
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '駐車場を登録しました。');
    }

    public function edit(Request $request, ParkingSpot $parkingSpot)
    {
        $parkingSpot->load(['postalcode.city.prefecture', 'images', 'rates']);
        Gate::authorize('update', $parkingSpot);
        $this->confirmation->beginEdit($request, $parkingSpot->id);

        $capacity = config('categories.parking_spot_capacity');
        $rateDayTypes = config('categories.parking_spot_rate_day_types');
        $rateUnitMinutes = config('categories.parking_spot_rate_unit_minutes');

        $address1 = $parkingSpot->postalcode->fullAddress();
        $formValues = [
            'name' => $parkingSpot->name,
            'postalcode' => $parkingSpot->postalcode->postalcode,
            'address1' => $address1,
            'address2' => str_replace($address1, '', $parkingSpot->address),
            'capacity' => $parkingSpot->capacity,
            'opening_time' => date('H:i', strtotime($parkingSpot->opening_time)),
            'closing_time' => date('H:i', strtotime($parkingSpot->closing_time)),
        ];

        $ratesInput = $parkingSpot->rates->map(fn ($rate) => [
            'day_type' => $rate->day_type,
            'start_time' => date('H:i', strtotime($rate->start_time)),
            'end_time' => date('H:i', strtotime($rate->end_time)),
            'unit_minutes' => $rate->unit_minutes,
            'rate' => $rate->rate,
            'free_minutes' => $rate->free_minutes,
            'no_free_minutes' => $rate->free_minutes === 0 ? '1' : '0',
            'max_rate' => $rate->max_rate,
            'no_max_rate' => $rate->max_rate === null ? '1' : '0',
        ])->values()->all() ?: [$this->defaultRateInput()];
        $imagePaths = $parkingSpot->image_paths;

        return view('parking_spot.edit', compact('parkingSpot', 'capacity', 'rateDayTypes', 'rateUnitMinutes', 'formValues', 'ratesInput', 'imagePaths'));
    }

    public function update(Request $request, ParkingSpot $parkingSpot)
    {
        $input = $this->confirmation->confirmedInput($request, ParkingSpotConfirmationService::MODE_EDIT);

        if ($input === null || (int) $input['id'] !== $parkingSpot->id) {
            return redirect()->route('home')
                ->with('error', '確認情報の有効期限が切れました。編集画面からやり直してください。');
        }

        Gate::authorize('update', $parkingSpot);

        if ($request->input('back') === 'back') {
            return redirect()->route('parking_spot.edit', $parkingSpot)->withInput($input);
        }

        try {
            $this->persistence->update($input, $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('parking_spot.edit', $parkingSpot)
                ->withErrors($exception->errors())
                ->withInput($input);
        }

        $this->confirmation->forget($request);
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '駐車場情報を更新しました。');
    }

    private function redirectToTrustedForm(Request $request)
    {
        $parkingSpotId = $this->confirmation->trustedParkingSpotId($request);

        return $parkingSpotId
            ? redirect()->route('parking_spot.edit', ['parkingSpot' => $parkingSpotId])
            : redirect()->route('parking_spot.create');
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
