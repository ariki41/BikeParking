<x-app-layout>
    <div class="bp-shell max-w-4xl">
        <a class="inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800"
            href="{{ route('parking_spot.show', $parkingSpot) }}#reviews">
            ← 駐輪場詳細へ戻る
        </a>

        <div class="mb-6 mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $parkingSpot->name }}のレビュー</h1>
                <x-rating-summary class="mt-2" :parking-spot="$parkingSpot" />
            </div>
            <p class="text-sm font-semibold text-slate-600">全{{ number_format($reviews->total()) }}件</p>
        </div>

        <div class="bp-panel">
            <div class="divide-y divide-slate-100 px-5">
                @forelse ($reviews as $review)
                    <x-review-item :review="$review" />
                @empty
                    <p class="py-5 text-sm text-slate-500">まだ評価はありません。</p>
                @endforelse
            </div>
        </div>

        @if ($reviews->hasPages())
            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
</x-app-layout>
