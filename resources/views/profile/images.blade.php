<x-app-layout>
    <div class="bp-shell max-w-4xl space-y-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">マイページ</h1>
            <p class="bp-muted mt-2">アカウント情報や投稿内容を管理できます。</p>
        </div>

        @include('profile.partials.navigation')

        <section class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8" aria-labelledby="my-images-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100" id="my-images-heading">投稿した画像</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">追加した駐輪場画像を投稿日時の新しい順に確認できます。</p>
                </div>
                <p class="text-sm font-semibold text-slate-600">全{{ number_format($images->total()) }}件</p>
            </div>

            <div class="mt-5 divide-y divide-slate-100 border-y border-slate-100">
                @forelse ($images as $image)
                    <article class="flex gap-4 py-5">
                        <img class="h-24 w-32 rounded object-cover" src="{{ \App\Models\ParkingSpot::imageUrlForPath($image->path) }}" alt="{{ $image->parkingSpot->name }}の投稿画像">
                        <div>
                            <a class="text-base font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('parking_spot.show', $image->parkingSpot) }}">
                                {{ $image->parkingSpot->name }}
                            </a>
                            <p class="mt-2 text-sm text-slate-600">投稿日時: {{ $image->created_at->format('Y年n月j日 H:i') }}</p>
                        </div>
                    </article>
                @empty
                    <div class="py-8 text-center">
                        <p class="text-base font-semibold text-slate-700">投稿した画像はまだありません。</p>
                        <p class="mt-2 text-sm text-slate-500">駐輪場の登録・編集画面から画像を追加できます。</p>
                        <a class="mt-5 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('search') }}">駐輪場を探す</a>
                    </div>
                @endforelse
            </div>

            @if ($images->hasPages())
                <div class="mt-6">{{ $images->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
