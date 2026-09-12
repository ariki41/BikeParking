<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            マイページ
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @include('profile.partials.navigation')

            <section class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8" aria-labelledby="my-reviews-heading">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100" id="my-reviews-heading">投稿したレビュー</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">投稿したレビューを更新日時の新しい順に確認できます。</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">全{{ number_format($reviews->total()) }}件</p>
                </div>

                <div class="mt-5 divide-y divide-slate-100 border-y border-slate-100">
                    @forelse ($reviews as $review)
                        <div class="py-5">
                            <a class="text-base font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.show', $review->parkingSpot) }}#reviews">
                                {{ $review->parkingSpot->name }}
                            </a>
                            <x-review-item class="pb-0" :review="$review" />
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <p class="text-base font-semibold text-slate-700">投稿したレビューはまだありません。</p>
                            <p class="mt-2 text-sm text-slate-500">駐輪場の詳細ページから評価・レビューを投稿できます。</p>
                            <a class="mt-5 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('search') }}">駐輪場を探す</a>
                        </div>
                    @endforelse
                </div>

                @if ($reviews->hasPages())
                    <div class="mt-6">{{ $reviews->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
