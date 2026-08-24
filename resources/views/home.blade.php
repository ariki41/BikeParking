<x-app-layout>
    <div class="bp-shell">
        @if (session('error'))
            <p class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </p>
        @endif

        @if (session('favorite_success'))
            <p class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('favorite_success') }}
            </p>
        @endif

        <section class="mb-8 rounded-lg border border-emerald-100 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-center">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">近くの駐輪場をすばやく探す</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                        駅名や地名から、料金・営業時間・場所を確認できる駐輪場検索サービスです。
                    </p>
                </div>
                <form class="rounded-lg border border-slate-200 bg-slate-50 p-4" method="GET" action="{{ route('search') }}">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <x-text-input class="w-full" id="keyword" name="keyword" type="text" :value="old('keyword')"
                            placeholder="駅名・地名を入力" />
                        <x-primary-button class="shrink-0">検索</x-primary-button>
                    </div>
                </form>
            </div>
        </section>

        <section>
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">新着の駐輪場</h2>
                    <p class="bp-muted mt-1">最近登録された駐輪場を確認できます。</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($parkingSpots as $parkingSpot)
                    <article class="bp-card-link overflow-hidden">
                        <a href="{{ route('parking_spot.show', $parkingSpot) }}">
                            <img class="h-40 w-full object-cover" src="{{ $parkingSpot->image_url }}" alt="駐輪場画像">
                        </a>
                        <div class="space-y-3 p-4">
                            <div>
                                <a class="text-lg font-semibold text-slate-900 hover:text-emerald-700"
                                    href="{{ route('parking_spot.show', $parkingSpot) }}">
                                    {{ $parkingSpot->name }}
                                </a>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $parkingSpot->address }}</p>
                                <x-rating-summary class="mt-2" :parking-spot="$parkingSpot" />
                            </div>
                            <x-rate-summary :parking-spot="$parkingSpot" />
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                <div>
                                    <a class="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
                                        href="{{ route('parking_spot.show', $parkingSpot) }}">
                                        詳細を見る
                                    </a>
                                    <span class="ml-2 text-xs text-slate-500">{{ $parkingSpot->capacity_label }}</span>
                                </div>
                                @auth
                                    <x-favorite-button :parking-spot="$parkingSpot" :favorited="$parkingSpot->is_favorited" compact />
                                @endauth
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($parkingSpots->isNotEmpty())
                <x-ad-slot class="mt-8" placement="home_footer" />
            @endif
        </section>
    </div>
</x-app-layout>
