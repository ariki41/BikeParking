<x-app-layout>
    <div class="bp-shell">
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
                    <h2 class="text-2xl font-bold text-slate-900">新着の駐車場</h2>
                    <p class="bp-muted mt-1">最近登録された駐輪場を確認できます。</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($parkingSpots as $parkingSpot)
                    <a class="bp-card-link" href="{{ route('parking_spot.show', ['id' => $parkingSpot->id]) }}">
                        <img class="h-40 w-full rounded-t-lg object-cover" src="{{ $parkingSpot->image_url }}" alt="駐車場画像">
                        <div class="space-y-3 p-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $parkingSpot->name }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $parkingSpot->address }}</p>
                                <x-rating-summary class="mt-2" :parking-spot="$parkingSpot" />
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-sm font-semibold text-emerald-700">詳細を見る</span>
                                <span class="text-xs text-slate-500">{{ $parkingSpot->capacity_label }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
