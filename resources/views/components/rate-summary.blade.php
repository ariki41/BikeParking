@props(['parkingSpot'])

@php
    $rate = $parkingSpot->representativeRate;
    $additionalRateCount = max(0, (int) $parkingSpot->rates_count - 1);
@endphp

<div {{ $attributes->class(['rounded-md border border-emerald-100 bg-emerald-50/60 px-3 py-2']) }}>
    <p class="text-xs font-semibold text-slate-500">代表料金</p>

    @if ($rate)
        <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <p class="font-bold text-emerald-700">{{ $rate->rate_label }}</p>
            @if ($additionalRateCount > 0)
                <span class="text-xs font-semibold text-slate-500">ほか{{ $additionalRateCount }}件の料金帯</span>
            @endif
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
            <span class="font-semibold text-slate-600">{{ $rate->day_type }}</span>
            <span>{{ $rate->time_range_label }}</span>
        </div>
    @else
        <p class="mt-1 text-sm font-semibold text-slate-500">料金未登録</p>
    @endif
</div>
