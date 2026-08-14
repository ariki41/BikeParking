<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">お気に入り</h1>
                <p class="bp-muted mt-2">お気に入りに登録した駐輪場を確認できます。</p>
            </div>
            <p class="text-sm font-semibold text-slate-600">お気に入り {{ $parkingSpots->total() }}件</p>
        </div>

        @if (session('favorite_success'))
            <p class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('favorite_success') }}
            </p>
        @endif

        @forelse ($parkingSpots as $parkingSpot)
            @if ($loop->first)
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @endif

            <article class="bp-card-link overflow-hidden">
                <a href="{{ route('parking_spot.show', $parkingSpot) }}">
                    <img class="h-40 w-full object-cover" src="{{ $parkingSpot->image_url }}" alt="駐輪場画像">
                </a>
                <div class="space-y-3 p-4">
                    <div>
                        <a class="text-lg font-semibold text-slate-900 hover:text-emerald-700" href="{{ route('parking_spot.show', $parkingSpot) }}">
                            {{ $parkingSpot->name }}
                        </a>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $parkingSpot->address }}</p>
                        <x-rating-summary class="mt-2" :parking-spot="$parkingSpot" />
                    </div>
                    <x-rate-summary :parking-spot="$parkingSpot" />
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <span class="text-xs text-slate-500">{{ $parkingSpot->capacity_label }}</span>
                        <x-favorite-button :parking-spot="$parkingSpot" :favorited="true" compact />
                    </div>
                </div>
            </article>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="bp-panel p-8 text-center">
                <p class="text-base font-semibold text-slate-700">お気に入りはまだありません。</p>
                <p class="mt-2 text-sm text-slate-500">駐輪場の詳細や一覧から追加できます。</p>
                <a class="mt-5 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('search') }}">
                    駐輪場を探す
                </a>
            </div>
        @endforelse

        @if ($parkingSpots->hasPages())
            <div class="mt-6">{{ $parkingSpots->links() }}</div>
        @endif
    </div>
</x-app-layout>
