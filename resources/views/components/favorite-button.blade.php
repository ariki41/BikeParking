@props([
    'parkingSpot',
    'favorited' => false,
    'compact' => false,
])

@php
    $isFavorited = (bool) $favorited;
@endphp

<form method="POST" action="{{ $isFavorited ? route('favorites.destroy', $parkingSpot) : route('favorites.store', $parkingSpot) }}">
    @csrf
    @if ($isFavorited)
        @method('DELETE')
    @endif

    <button
        class="inline-flex items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 {{ $isFavorited ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-slate-300 bg-white text-slate-700 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700' }}"
        type="submit" aria-pressed="{{ $isFavorited ? 'true' : 'false' }}">
        <span aria-hidden="true">{{ $isFavorited ? '♥' : '♡' }}</span>
        <span>{{ $isFavorited ? ($compact ? '解除' : 'お気に入り解除') : ($compact ? '追加' : 'お気に入り追加') }}</span>
        <span class="text-xs font-medium">({{ $parkingSpot->favorites_count ?? 0 }}件)</span>
    </button>
</form>
