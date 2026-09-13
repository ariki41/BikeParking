<?php

namespace App\Http\Requests;

use App\Models\ParkingSpot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParkingSpotReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var ParkingSpot $parkingSpot */
        $parkingSpot = $this->route('parkingSpot');

        return [
            'reason' => ['required', 'string', 'max:2000'],
            'parking_spot_update_history_id' => [
                'nullable', 'integer',
                Rule::exists('parking_spot_update_histories', 'id')->where('parking_spot_id', $parkingSpot->id),
            ],
        ];
    }
}
