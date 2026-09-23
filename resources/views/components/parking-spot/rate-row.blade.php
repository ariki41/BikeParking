@props([
    'index',
    'rate',
    'rateDayTypes',
    'rateUnitMinutes',
    'maxRatePeriods',
    'messages' => [],
    'template' => false,
])

@php
    $namePrefix = $template ? null : "rates[{$index}]";
    $noFreeMinutes = (bool) ($rate['no_free_minutes'] ?? (int) ($rate['free_minutes'] ?? 0) === 0);
    $noMaxRate = (bool) ($rate['no_max_rate'] ?? false);
    $isFree = (bool) ($rate['is_free'] ?? (int) ($rate['rate'] ?? 0) === 0);
@endphp

<div class="rate-item mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-rate-item>
    <div class="mb-4 flex items-center justify-between gap-4">
        <h3 class="font-semibold text-slate-800">料金帯<span class="rate-number">{{ $template ? '' : $index + 1 }}</span>
        </h3>
        <button class="delete-rate-button bp-danger-link" data-delete-rate type="button">
            削除
        </button>
    </div>

    @if (count($messages) > 0)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600">
            @foreach ($messages as $fieldMessages)
                @foreach ($fieldMessages as $message)
                    <div>{{ $message }}</div>
                @endforeach
            @endforeach
        </div>
    @endif

    <div class="mb-4">
        <x-input-label>適用曜日</x-input-label>
        <select class="bp-select" data-rate-field="day_type"
            @if ($namePrefix !== null) name="{{ $namePrefix }}[day_type]" @endif>
            @foreach ($rateDayTypes as $key => $value)
                <option value="{{ $key }}" @selected(($rate['day_type'] ?? '全日') === $key)>{{ $value }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-input-label>料金開始時間</x-input-label>
            <input class="bp-input" data-rate-field="start_time" type="time"
                value="{{ $rate['start_time'] ?? '00:00' }}" required
                @if ($namePrefix !== null) name="{{ $namePrefix }}[start_time]" @endif>
        </div>

        <div>
            <x-input-label>料金終了時間</x-input-label>
            <input class="bp-input" data-rate-field="end_time" type="time" value="{{ $rate['end_time'] ?? '00:00' }}"
                required @if ($namePrefix !== null) name="{{ $namePrefix }}[end_time]" @endif>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-input-label>料金単位</x-input-label>
            <select
                class="rate-unit-minutes-input bp-select {{ $isFree ? 'cursor-not-allowed bg-slate-100 text-slate-500' : 'bg-white' }}"
                data-rate-field="unit_minutes" required @disabled($isFree)
                @if ($namePrefix !== null) name="{{ $namePrefix }}[unit_minutes]" @endif>
                @foreach ($rateUnitMinutes as $minutes => $label)
                    <option value="{{ $minutes }}" @selected((int) ($rate['unit_minutes'] ?? 30) === (int) $minutes)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="free-rate-unit-minutes-value" data-rate-hidden-field="unit_minutes" type="hidden"
                value="{{ $rate['unit_minutes'] ?? 30 }}" @disabled(!$isFree)
                @if ($namePrefix !== null) name="{{ $namePrefix }}[unit_minutes]" @endif>
        </div>

        <div>
            <x-input-label>料金（円）</x-input-label>
            <input
                class="rate-input bp-input {{ $isFree ? 'cursor-not-allowed bg-slate-100 text-slate-500' : 'bg-white' }}"
                data-rate-field="rate" type="number" value="{{ $rate['rate'] ?? '' }}" min="0" required
                placeholder="例：100" @readonly($isFree)
                @if ($namePrefix !== null) name="{{ $namePrefix }}[rate]" @endif>
            <label
                class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                <input
                    class="free-parking-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                    data-rate-field="is_free" type="checkbox" value="1" @checked($isFree)
                    @if ($namePrefix !== null) name="{{ $namePrefix }}[is_free]" @endif>
                <span>無料</span>
            </label>
            <p class="free-rate-notice {{ $isFree ? '' : 'hidden' }} mt-2 text-sm text-slate-500">
                無料をチェックすると料金単位・無料時間・最大料金・適用期間を無効化します。
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-input-label>最初の無料時間（分）</x-input-label>
            <input
                class="free-minutes-input bp-input {{ $isFree || $noFreeMinutes ? 'cursor-not-allowed bg-slate-100 text-slate-500' : 'bg-white' }}"
                data-rate-field="free_minutes" type="number" value="{{ $rate['free_minutes'] ?? 0 }}" min="0"
                placeholder="例：30" @disabled($isFree) @readonly($noFreeMinutes)
                @if ($namePrefix !== null) name="{{ $namePrefix }}[free_minutes]" @endif>
            <label
                class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                <input
                    class="no-free-minutes-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                    data-rate-field="no_free_minutes" type="checkbox" value="1" @checked($noFreeMinutes)
                    @disabled($isFree)
                    @if ($namePrefix !== null) name="{{ $namePrefix }}[no_free_minutes]" @endif>
                <span>無料時間なし</span>
            </label>
        </div>

        <div>
            <x-input-label>最大料金（円）</x-input-label>
            <input
                class="max-rate-input bp-input {{ $isFree || $noMaxRate ? 'cursor-not-allowed bg-slate-100 text-slate-500' : 'bg-white' }}"
                data-rate-field="max_rate" type="number" value="{{ $rate['max_rate'] ?? '' }}" min="1"
                placeholder="例：1200" @disabled($isFree || $noMaxRate)
                @if ($namePrefix !== null) name="{{ $namePrefix }}[max_rate]" @endif>
            <label
                class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                <input
                    class="no-max-rate-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                    data-rate-field="no_max_rate" type="checkbox" value="1" @checked($noMaxRate)
                    @disabled($isFree)
                    @if ($namePrefix !== null) name="{{ $namePrefix }}[no_max_rate]" @endif>
                <span>最大料金なし</span>
            </label>
            <div class="mt-3">
                <x-input-label>最大料金の適用期間</x-input-label>
                <select
                    class="max-rate-condition-input bp-select {{ $isFree || $noMaxRate ? 'cursor-not-allowed bg-slate-100 text-slate-500' : 'bg-white' }}"
                    data-rate-field="max_rate_period" @disabled($isFree || $noMaxRate)
                    @if ($namePrefix !== null) name="{{ $namePrefix }}[max_rate_period]" @endif>
                    @foreach ($maxRatePeriods as $value => $label)
                        <option value="{{ $value }}" @selected(($rate['max_rate_period'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                    <input
                        class="max-rate-condition-input rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                        data-rate-field="max_rate_repeats" type="checkbox" value="1" @checked($rate['max_rate_repeats'] ?? false)
                        @disabled($isFree || $noMaxRate)
                        @if ($namePrefix !== null) name="{{ $namePrefix }}[max_rate_repeats]" @endif>
                    <span>繰り返し適用する</span>
                </label>
            </div>
        </div>
    </div>
</div>
