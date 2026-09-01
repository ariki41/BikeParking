<x-app-layout>
    <div class="bp-shell">
        @if (session('favorite_success'))
            <p class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('favorite_success') }}
            </p>
        @endif

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $parkingSpot->name }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    〒{{ substr($parkingSpot->postalcode->postalcode, 0, 3) }}-{{ substr($parkingSpot->postalcode->postalcode, 3, 4) }}
                </p>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $parkingSpot->address }}</p>
                <x-rating-summary class="mt-2" :parking-spot="$parkingSpot" />
            </div>
            @auth
                <div class="flex flex-wrap items-center gap-3">
                    <x-favorite-button :parking-spot="$parkingSpot" :favorited="$parkingSpot->is_favorited" />
                    <a href="{{ route('parking_spot.edit', $parkingSpot) }}">
                        <x-primary-button tag="a">編集</x-primary-button>
                    </a>
                </div>
            @endauth
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-6">
                <div class="bp-panel">
                    @php($imageUrls = $parkingSpot->image_urls)
                    <div x-data="{ activeImage: @js($imageUrls[0]) }">
                        <img class="h-72 w-full object-cover" x-bind:src="activeImage" src="{{ $imageUrls[0] }}"
                            alt="駐輪場の写真">

                        @if (count($imageUrls) > 1)
                            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 p-3 sm:grid-cols-4">
                                @foreach ($imageUrls as $position => $imageUrl)
                                    <button class="overflow-hidden rounded-md border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                        type="button" x-on:click="activeImage = @js($imageUrl)"
                                        x-bind:class="activeImage === @js($imageUrl) ? 'border-emerald-500' : 'border-transparent'"
                                        aria-label="画像{{ $position + 1 }}を表示">
                                        <img class="h-20 w-full object-cover" src="{{ $imageUrl }}"
                                            alt="駐輪場の写真 {{ $position + 1 }}">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bp-panel">
                    <div class="bp-panel-header">
                        <h2 class="bp-section-title">料金</h2>
                    </div>
                    <div class="p-5">
                        @if ($parkingSpot->rates->isEmpty())
                            <span class="text-sm text-slate-500">料金未登録</span>
                        @else
                            <div class="max-w-full overflow-x-auto">
                                <table class="min-w-full table-auto border-collapse text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">
                                            <th class="whitespace-nowrap px-3 py-3">区分</th>
                                            <th class="whitespace-nowrap px-3 py-3">時間帯</th>
                                            <th class="whitespace-nowrap px-3 py-3">料金</th>
                                            <th class="whitespace-nowrap px-3 py-3">最大料金</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($parkingSpot->rates as $rate)
                                            <tr class="text-slate-700">
                                                <td class="whitespace-nowrap px-3 py-3 font-semibold">{{ $rate->day_type }}</td>
                                                <td class="whitespace-nowrap px-3 py-3">{{ $rate->time_range_label }}</td>
                                                <td class="min-w-40 px-3 py-3 font-semibold text-emerald-700">
                                                    {{ $rate->base_rate_label }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-3">{{ $rate->max_rate_label }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bp-panel" id="reviews">
                    <div class="bp-panel-header flex flex-wrap items-center justify-between gap-3">
                        <h2 class="bp-section-title">評価・レビュー</h2>
                        <x-rating-summary :parking-spot="$parkingSpot" />
                    </div>

                    <div class="border-b border-slate-100 p-5">
                        @if (session('review_success'))
                            <p class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                {{ session('review_success') }}
                            </p>
                        @endif

                        @auth
                            <h3 class="text-base font-semibold text-slate-900">
                                {{ $userReview ? 'あなたの評価を更新' : 'この駐輪場を評価' }}
                            </h3>
                            <form class="mt-4 space-y-4" method="POST" action="{{ route('reviews.store', $parkingSpot) }}">
                                @csrf

                                <div>
                                    <x-input-label for="rating" value="評価" />
                                    <select class="bp-select" id="rating" name="rating" required>
                                        <option value="">選択してください</option>
                                        @for ($rating = 5; $rating >= 1; $rating--)
                                            <option value="{{ $rating }}" @selected((int) old('rating', $userReview?->rating) === $rating)>
                                                {{ $rating }} - {{ str_repeat('★', $rating) }}
                                            </option>
                                        @endfor
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('rating')" />
                                </div>

                                <div>
                                    <x-input-label for="comment" value="コメント" />
                                    <textarea class="bp-input min-h-32" id="comment" name="comment" maxlength="1000" required>{{ old('comment', $userReview?->comment) }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('comment')" />
                                </div>

                                <x-primary-button>{{ $userReview ? '評価を更新' : '評価を投稿' }}</x-primary-button>
                            </form>
                        @else
                            <p class="text-sm text-slate-600">
                                評価を投稿するには
                                <a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('login') }}">ログイン</a>
                                してください。
                            </p>
                        @endauth
                    </div>

                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                        <h3 class="text-sm font-semibold text-slate-700">レビュー</h3>
                        <span class="text-sm text-slate-500">全{{ number_format($parkingSpot->reviews_count) }}件</span>
                    </div>

                    <div class="divide-y divide-slate-100 px-5">
                        @forelse ($recentReviews as $review)
                            <x-review-item :review="$review" />
                        @empty
                            <p class="py-5 text-sm text-slate-500">まだ評価はありません。</p>
                        @endforelse
                    </div>

                    @if ($parkingSpot->reviews_count > 10)
                        <div class="border-t border-slate-100 px-5 py-4 text-center">
                            <a class="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
                                href="{{ route('reviews.index', $parkingSpot) }}">
                                すべてのレビューを見る（{{ number_format($parkingSpot->reviews_count) }}件）
                            </a>
                        </div>
                    @endif
                </div>

                <div class="bp-panel">
                    <div class="bp-panel-header">
                        <h2 class="bp-section-title">更新履歴</h2>
                    </div>
                    <div class="divide-y divide-slate-100 px-5">
                        @forelse ($parkingSpot->updateHistories->take(10) as $history)
                            <div class="py-4 text-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-800">
                                        {{ $history->user?->name ?? '退会済みユーザー' }}
                                    </span>
                                    <time class="text-xs text-slate-500" datetime="{{ $history->created_at?->toIso8601String() }}">
                                        {{ $history->created_at?->format('Y-m-d H:i') }}
                                    </time>
                                </div>
                                <p class="mt-1 text-slate-600">変更項目: {{ $history->change_summary }}</p>
                            </div>
                        @empty
                            <p class="py-5 text-sm text-slate-500">更新履歴はありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="bp-panel">
                    <x-leaflet-map class="h-80 w-full bg-slate-100" :latitude="$parkingSpot->latitude"
                        :longitude="$parkingSpot->longitude" :zoom="17" :markers="[[
                            'latitude' => $parkingSpot->latitude,
                            'longitude' => $parkingSpot->longitude,
                        ]]" />
                </div>

                <div class="bp-panel">
                    <div class="bp-panel-header">
                        <h2 class="bp-section-title">基本情報</h2>
                    </div>
                    <dl class="divide-y divide-slate-100 p-5 text-sm">
                        <div class="grid grid-cols-[140px_minmax(0,1fr)] gap-3 py-3 first:pt-0">
                            <dt class="whitespace-nowrap font-semibold text-slate-500">収容台数</dt>
                            <dd class="text-slate-900">{{ $parkingSpot->capacity_label }}</dd>
                        </div>
                        <div class="grid grid-cols-[140px_minmax(0,1fr)] gap-3 py-3">
                            <dt class="whitespace-nowrap font-semibold text-slate-500">駐車可能な排気量</dt>
                            <dd class="text-slate-900">{{ $parkingSpot->max_displacement_class?->label() ?? '未設定' }}</dd>
                        </div>
                        <div class="grid grid-cols-[140px_minmax(0,1fr)] gap-3 pb-2 pt-3">
                            <dt class="whitespace-nowrap font-semibold text-slate-500">営業時間</dt>
                            <dd class="text-slate-900">{{ $parkingSpot->opening_time }} ～ {{ $parkingSpot->closing_time }}</dd>
                        </div>
                    </dl>
                    <div class="px-5 pb-3 pt-1 text-[11px] text-slate-400">
                        <div class="flex items-center justify-end gap-2">
                            <span>作成日 {{ optional($parkingSpot->created_at)->format('Y-m-d') }}</span>
                            <span class="text-slate-300">/</span>
                            <span>更新日 {{ optional($parkingSpot->updated_at)->format('Y-m-d') }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <x-ad-slot class="mt-10" placement="parking_spot_footer" />
    </div>
</x-app-layout>
