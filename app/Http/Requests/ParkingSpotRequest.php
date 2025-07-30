<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Postalcode;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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

                    if(!empty($address) && $address->prefecture . $address->city . $address->town != $value) {
                        $fail('郵便番号と住所が一致しません。');
                    }
                }
            ],
            'address2' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
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
        ];
    }
}
