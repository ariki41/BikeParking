@props(['parkingSpot'])

@if ((int) ($parkingSpot->reviews_count ?? 0) > 0)
    <span {{ $attributes->class(['inline-flex items-center gap-1 text-sm font-semibold text-slate-700']) }}>
        <span class="text-amber-500" aria-hidden="true">★</span>
        <span>{{ number_format((float) $parkingSpot->reviews_avg_rating, 1) }}</span>
        <span class="font-normal text-slate-500">({{ number_format($parkingSpot->reviews_count) }}件)</span>
    </span>
@else
    <span {{ $attributes->class(['text-sm text-slate-500']) }}>評価なし</span>
@endif
