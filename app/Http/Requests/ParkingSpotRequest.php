<?php

namespace App\Http\Requests;

use App\Models\Postalcode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParkingSpotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'postalcode' => 'required|regex:/^\d{3}-?\d{4}$/',
            'address1' => ['required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    $postalcode = request()->input('postalcode');
                    $address = Postalcode::getAddress($postalcode)->first();

                    if (! empty($address) && $address->prefecture.$address->city.$address->town != $value) {
                        $fail('郵便番号と住所が一致しません。');
                    }
                },
            ],
            'address2' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'rates' => 'required|array|min:1|max:4',
            'rates.*.day_type' => ['required', 'string', Rule::in(array_keys(config('categories.parking_spot_rate_day_types')))],
            'rates.*.start_time' => 'required|date_format:H:i',
            'rates.*.end_time' => 'required|date_format:H:i',
            'rates.*.unit_minutes' => 'required|integer|min:1',
            'rates.*.rate' => 'required|integer|min:0',
            'rates.*.free_minutes' => 'nullable|integer|min:0',
            'rates.*.no_max_rate' => 'nullable|boolean',
            'rates.*.max_rate' => 'required_unless:rates.*.no_max_rate,1|nullable|integer|min:0',
        ];
    }

    /**
     * エラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '駐車場名は必須です。',
            'name.string' => '駐車場名は文字列で入力してください。',
            'name.max' => '駐車場名は255文字以内で入力してください。',

            'postalcode.required' => '郵便番号は必須です。',
            'postalcode.regex' => '郵便番号の形式が正しくありません。例: 123-4567 または 1234567',

            'address1.required' => '都道府県・市区町村・町域は必須です。',
            'address1.string' => '都道府県・市区町村・町域は文字列で入力してください。',
            'address1.max' => '都道府県・市区町村・町域は255文字以内で入力してください。',

            'address2.required' => '続きの住所は必須です。',
            'address2.string' => '続きの住所は文字列で入力してください。',
            'address2.max' => '続きの住所は255文字以内で入力してください。',

            'capacity.required' => '駐車場台数は必須です。',
            'capacity.integer' => '駐車場台数は整数で入力してください。',
            'capacity.min' => '駐車場台数を設定してください。',

            'opening_time.required' => '開場時間は必須です。',
            'opening_time.date_format' => '開場時間の形式が正しくありません。例: 10:00',

            'closing_time.required' => '閉場時間は必須です。',
            'closing_time.date_format' => '閉場時間の形式が正しくありません。例: 22:00',

            'rates.required' => '料金は必須です。',
            'rates.array' => '料金の形式が正しくありません。',
            'rates.min' => '料金は1件以上入力してください。',
            'rates.max' => '料金帯は4件まで入力できます。',

            'rates.*.day_type.required' => '料金区分は必須です。',
            'rates.*.day_type.string' => '料金区分は文字列で入力してください。',
            'rates.*.day_type.in' => '料金区分を選択してください。',

            'rates.*.start_time.required' => '料金開始時間は必須です。',
            'rates.*.start_time.date_format' => '料金開始時間の形式が正しくありません。例: 08:00',

            'rates.*.end_time.required' => '料金終了時間は必須です。',
            'rates.*.end_time.date_format' => '料金終了時間の形式が正しくありません。例: 20:00',

            'rates.*.unit_minutes.required' => '料金単位は必須です。',
            'rates.*.unit_minutes.integer' => '料金単位は整数で入力してください。',
            'rates.*.unit_minutes.min' => '料金単位は1分以上で入力してください。',

            'rates.*.rate.required' => '料金は必須です。',
            'rates.*.rate.integer' => '料金は整数で入力してください。',
            'rates.*.rate.min' => '料金は0円以上で入力してください。',

            'rates.*.free_minutes.integer' => '無料時間は整数で入力してください。',
            'rates.*.free_minutes.min' => '無料時間は0分以上で入力してください。',

            'rates.*.max_rate.integer' => '最大料金は整数で入力してください。',
            'rates.*.max_rate.min' => '最大料金は0円以上で入力してください。',
            'rates.*.max_rate.required_unless' => '最大料金なしを選択しない場合、最大料金は必須です。',
        ];
    }
}
