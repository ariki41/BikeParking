<x-app-layout title="登録した駐輪場" robots="noindex, nofollow">
    <div class="bp-shell max-w-4xl space-y-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">マイページ</h1>
            <p class="bp-muted mt-2">アカウント情報や投稿内容を管理できます。</p>
        </div>

        @include('profile.partials.navigation')

        <section class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8" aria-labelledby="my-parking-spots-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100" id="my-parking-spots-heading">登録した駐輪場</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">自分が登録した駐輪場を新しい順に確認できます。</p>
                </div>
                <p class="text-sm font-semibold text-slate-600">全{{ number_format($parkingSpots->total()) }}件</p>
            </div>

            <div class="mt-5 divide-y divide-slate-100 border-y border-slate-100">
                @forelse ($parkingSpots as $parkingSpot)
                    <article class="flex flex-wrap items-center justify-between gap-3 py-5">
                        <div>
                            <a class="text-base font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.show', $parkingSpot) }}">
                                {{ $parkingSpot->name }}
                            </a>
                            <p class="mt-2 text-sm text-slate-600">登録日時: {{ $parkingSpot->created_at->format('Y年n月j日 H:i') }}</p>
                            @if (! $parkingSpot->is_published)
                                <p class="mt-1 text-sm font-semibold text-slate-600">閉鎖済み</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-sm font-semibold">
                            <a class="text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.show', $parkingSpot) }}">詳細</a>
                            @if ($parkingSpot->is_published)
                                <a class="text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.edit', $parkingSpot) }}">編集</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="py-8 text-center">
                        <p class="text-base font-semibold text-slate-700">登録した駐輪場はまだありません。</p>
                        <p class="mt-2 text-sm text-slate-500">駐輪場の情報を登録して、ほかの利用者と共有できます。</p>
                        <a class="mt-5 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.create') }}">駐輪場を登録する</a>
                    </div>
                @endforelse
            </div>

            @if ($parkingSpots->hasPages())
                <div class="mt-6">{{ $parkingSpots->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
