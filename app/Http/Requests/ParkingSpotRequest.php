<?php

namespace App\Http\Requests;

use App\Models\Postalcode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParkingSpotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rates = collect($this->input('rates', []))
            ->map(function ($rate) {
                if (($rate['no_free_minutes'] ?? false)) {
                    $rate['free_minutes'] = 0;
                }

                return $rate;
            })
            ->all();

        $this->merge(['rates' => $rates]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $imageCount = count((array) $this->file('images', [])) + ($this->hasFile('image') ? 1 : 0);

            if ($imageCount > 4) {
                $validator->errors()->add('images', '画像は4枚までアップロードできます。');
            }

            $this->validateRateTimeConflicts($validator);
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|exists:parking_spots,id',
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
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:20480',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
            'image_paths' => 'nullable|array|max:4',
            'image_paths.*' => 'string|max:255',
            'image_path' => 'nullable|string|max:255',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'rates' => 'required|array|min:1|max:4',
            'rates.*.day_type' => ['required', 'string', Rule::in(array_keys(config('categories.parking_spot_rate_day_types')))],
            'rates.*.start_time' => 'required|date_format:H:i',
            'rates.*.end_time' => 'required|date_format:H:i',
            'rates.*.unit_minutes' => ['required', 'integer', Rule::in(array_keys(config('categories.parking_spot_rate_unit_minutes')))],
            'rates.*.rate' => 'required|integer|min:0',
            'rates.*.free_minutes' => 'nullable|integer|min:0',
            'rates.*.no_free_minutes' => 'nullable|boolean',
            'rates.*.no_max_rate' => 'nullable|boolean',
            'rates.*.max_rate' => 'required_unless:rates.*.no_max_rate,1|nullable|integer|min:1',
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
            'id.integer' => '編集対象の情報が正しくありません。',
            'id.exists' => '編集対象の駐輪場が見つかりません。',
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

            'images.array' => '画像の選択内容が正しくありません。',
            'images.max' => '画像は4枚までアップロードできます。',
            'images.*.image' => '画像ファイルを選択してください。',
            'images.*.mimes' => '画像は jpg / jpeg / png / webp 形式でアップロードしてください。',
            'images.*.max' => '画像は1枚あたり20MB以下でアップロードしてください。',
            'image.image' => '画像ファイルを選択してください。',
            'image.mimes' => '画像は jpg / jpeg / png / webp 形式でアップロードしてください。',
            'image.max' => '画像は20MB以下でアップロードしてください。',
            'image_paths.array' => '画像の保持情報が正しくありません。',
            'image_paths.max' => '画像は4枚まで保持できます。',
            'image_paths.*.string' => '画像の保持情報が正しくありません。',
            'image_paths.*.max' => '画像の保持情報が長すぎます。',
            'image_path.string' => '画像の保持情報が正しくありません。',
            'image_path.max' => '画像の保持情報が長すぎます。',

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
            'rates.*.unit_minutes.in' => '料金単位を選択してください。',

            'rates.*.rate.required' => '料金は必須です。',
            'rates.*.rate.integer' => '料金は整数で入力してください。',
            'rates.*.rate.min' => '料金は0円以上で入力してください。',

            'rates.*.free_minutes.integer' => '無料時間は整数で入力してください。',
            'rates.*.free_minutes.min' => '無料時間は0分以上で入力してください。',

            'rates.*.max_rate.integer' => '最大料金は整数で入力してください。',
            'rates.*.max_rate.min' => '最大料金は1円以上で入力してください。',
            'rates.*.max_rate.required_unless' => '最大料金なしを選択しない場合、最大料金は必須です。',
        ];
    }

    private function validateRateTimeConflicts(Validator $validator): void
    {
        $rates = $this->input('rates');

        if (! is_array($rates) || $validator->errors()->has('rates')) {
            return;
        }

        $validatedRates = collect($rates)
            ->map(fn ($rate, $index) => ['index' => $index, 'rate' => $rate])
            ->filter(fn (array $item) => $this->isReadyForTimeConflictCheck($item['rate'], $item['index'], $validator))
            ->values();

        for ($left = 0; $left < $validatedRates->count(); $left++) {
            for ($right = $left + 1; $right < $validatedRates->count(); $right++) {
                $leftRate = $validatedRates[$left];
                $rightRate = $validatedRates[$right];

                if (! $this->hasDayTypeOverlap($leftRate['rate']['day_type'], $rightRate['rate']['day_type'])) {
                    continue;
                }

                if (! $this->hasTimeOverlap($leftRate['rate'], $rightRate['rate'])) {
                    continue;
                }

                $message = $this->buildTimeConflictMessage(
                    $leftRate['index'],
                    $leftRate['rate']['day_type'],
                    $rightRate['index'],
                    $rightRate['rate']['day_type'],
                );

                $validator->errors()->add("rates.{$leftRate['index']}.time_conflict", $message);
                $validator->errors()->add("rates.{$rightRate['index']}.time_conflict", $message);
            }
        }
    }

    private function isReadyForTimeConflictCheck(array $rate, int $index, Validator $validator): bool
    {
        $fields = ['day_type', 'start_time', 'end_time'];

        foreach ($fields as $field) {
            if ($validator->errors()->has("rates.{$index}.{$field}")) {
                return false;
            }
        }

        if (! isset($rate['day_type'], $rate['start_time'], $rate['end_time'])) {
            return false;
        }

        return in_array($rate['day_type'], array_keys(config('categories.parking_spot_rate_day_types')), true)
            && preg_match('/^\d{2}:\d{2}$/', $rate['start_time']) === 1
            && preg_match('/^\d{2}:\d{2}$/', $rate['end_time']) === 1;
    }

    private function hasTimeOverlap(array $leftRate, array $rightRate): bool
    {
        foreach ($this->expandTimeRanges($leftRate['start_time'], $leftRate['end_time']) as [$leftStart, $leftEnd]) {
            foreach ($this->expandTimeRanges($rightRate['start_time'], $rightRate['end_time']) as [$rightStart, $rightEnd]) {
                if (max($leftStart, $rightStart) < min($leftEnd, $rightEnd)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasDayTypeOverlap(string $leftDayType, string $rightDayType): bool
    {
        return count(array_intersect(
            $this->dayTypeScopes($leftDayType),
            $this->dayTypeScopes($rightDayType),
        )) > 0;
    }

    private function dayTypeScopes(string $dayType): array
    {
        return match ($dayType) {
            '平日' => ['weekday'],
            '土日祝' => ['holiday'],
            default => ['weekday', 'holiday'],
        };
    }

    private function expandTimeRanges(string $startTime, string $endTime): array
    {
        $start = $this->timeToMinutes($startTime);
        $end = $this->timeToMinutes($endTime);

        if ($start === $end) {
            return [[0, 1440]];
        }

        if ($start < $end) {
            return [[$start, $end]];
        }

        return [
            [$start, 1440],
            [0, $end],
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function buildTimeConflictMessage(int $leftIndex, string $leftDayType, int $rightIndex, string $rightDayType): string
    {
        $leftNumber = $leftIndex + 1;
        $rightNumber = $rightIndex + 1;

        return "料金帯{$leftNumber}の「{$leftDayType}」と料金帯{$rightNumber}の「{$rightDayType}」は適用条件が重複しています。";
    }
}
