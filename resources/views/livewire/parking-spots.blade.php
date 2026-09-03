<div class="flex min-h-0 flex-1 flex-col">
    <form class="shrink-0" method="GET" action="{{ route('search') }}" x-data>
        <div class="space-y-4">
            <h1 class="text-2xl font-bold text-slate-900">駐輪場を探す</h1>
            <div class="flex items-center gap-2">
                <x-text-input class="w-full" id="keyword" name="keyword" type="text" placeholder="駅名・地名を入力"
                    :value="$keyword" />
                <x-primary-button class="shrink-0">検索</x-primary-button>
            </div>
            <fieldset>
                <legend class="sr-only">駐車したいバイクの排気量</legend>
                <div class="flex min-h-5 items-center justify-between gap-3 text-sm leading-5">
                    <span class="font-medium text-slate-700" aria-hidden="true">駐車したいバイクの排気量</span>
                    @if ($engineDisplacement !== null)
                        <a class="shrink-0 font-semibold text-emerald-700 hover:text-emerald-800 hover:underline"
                            href="{{ route('search', array_filter([
                                'keyword' => $keyword,
                                'lat' => $latitude,
                                'lon' => $longitude,
                                'capacity' => $capacityQuery,
                                'open_24_hours' => $open24HoursQuery,
                                'has_free_time' => $hasFreeTimeQuery,
                                'max_rate' => $maxRateQuery,
                                'zoom' => $zoom,
                            ], fn ($value) => $value !== null && $value !== '' && $value !== [])) }}"
                            aria-label="排気量条件をクリア">
                            クリア
                        </a>
                    @endif
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($displacementClasses as $displacementClass)
                        <label class="cursor-pointer">
                            <input class="peer sr-only" name="engine_displacement" type="radio"
                                value="{{ $displacementClass->value }}" @checked($engineDisplacement === $displacementClass->value)
                                x-on:change="$el.form.requestSubmit()">
                            <span
                                class="flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-center text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                                {{ $displacementClass->searchLabel() }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @if ($capacityQuery !== '')
                <input name="capacity" type="hidden" value="{{ $capacityQuery }}">
            @endif
            @if ($open24HoursQuery !== '')
                <input name="open_24_hours" type="hidden" value="1">
            @endif
            @if ($hasFreeTimeQuery !== '')
                <input name="has_free_time" type="hidden" value="1">
            @endif
            @if ($maxRateQuery !== '')
                <input name="max_rate" type="hidden" value="{{ $maxRateQuery }}">
            @endif

            <input name="lat" type="hidden" value="{{ $latitude }}">
            <input name="lon" type="hidden" value="{{ $longitude }}">
            <input name="zoom" type="hidden" value="{{ $zoom }}">

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                    <p class="text-sm text-red-600">{{ session('error') }}</p>
                </div>
            @endif
        </div>
    </form>

    <section class="mt-4 shrink-0 rounded-lg border border-slate-200 bg-slate-50" x-data="{ open: @js($errors->has('maxRateDraft')) }">
        <button class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left" type="button"
            x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-controls="parking-spot-filters">
            <span class="flex items-center gap-2">
                <span class="font-semibold text-slate-900">絞り込み</span>
                @if ($activeFilterLabels !== [])
                    <span class="bp-badge">{{ count($activeFilterLabels) }}件適用中</span>
                @endif
            </span>
            <span class="text-sm font-semibold text-emerald-700" x-text="open ? '閉じる' : '開く'"></span>
        </button>

        @if ($activeFilterLabels !== [])
            <div class="flex flex-wrap gap-1.5 border-t border-slate-200 px-3 py-2" aria-label="適用中の絞り込み条件">
                @foreach ($activeFilterLabels as $label)
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ $label }}</span>
                @endforeach
            </div>
        @endif

        <form class="space-y-4 border-t border-slate-200 p-3" id="parking-spot-filters" wire:submit="applyFilters"
            x-show="open" x-cloak>
            <fieldset>
                <legend class="text-sm font-semibold text-slate-800">収容台数（複数選択可）</legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach (config('categories.parking_spot_capacity') as $value => $label)
                        <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox"
                                value="{{ $value }}" wire:model="capacityDraft">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-2 sm:grid-cols-2">
                <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                    <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox"
                        wire:model="open24HoursDraft">
                    <span>24時間営業</span>
                </label>
                <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                    <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox"
                        wire:model="hasFreeTimeDraft">
                    <span>無料時間あり</span>
                </label>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-800" for="max-rate-filter">最大料金上限</label>
                <div class="mt-2 flex items-center gap-2">
                    <input class="bp-input" id="max-rate-filter" type="number" min="1" inputmode="numeric"
                        placeholder="例: 1000" wire:model="maxRateDraft"
                        aria-invalid="{{ $errors->has('maxRateDraft') ? 'true' : 'false' }}"
                        @if ($errors->has('maxRateDraft')) aria-describedby="max-rate-filter-error" @endif>
                    <span class="shrink-0 text-sm text-slate-600">円以下</span>
                </div>
                @error('maxRateDraft')
                    <p class="mt-1 text-sm text-red-600" id="max-rate-filter-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <x-primary-button class="justify-center" type="submit">この条件で絞り込む</x-primary-button>
                <button class="min-h-10 rounded-md px-4 text-sm font-semibold text-slate-600 hover:bg-slate-200 hover:text-slate-900"
                    type="button" wire:click="clearFilters">
                    条件をクリア
                </button>
            </div>
        </form>
    </section>

    @if (session('favorite_success'))
        <p class="mt-4 shrink-0 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
            {{ session('favorite_success') }}
        </p>
    @endif

    <div class="mt-4 h-[calc(100vh-17rem)] overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 lg:h-auto lg:min-h-0 lg:flex-1"
        id="parking-spots">
        @if ($hasSearched && count($spots) === 0)
            <div class="flex min-h-40 items-center justify-center p-6 text-center">
                <p class="text-sm leading-6 text-slate-600">条件に一致する駐輪場がありません。条件または地図範囲を変更してください。</p>
            </div>
        @else
            <div class="space-y-3 p-3">
                @foreach ($spots as $spot)
                    <article class="bp-card-link overflow-hidden">
                        <div class="flex gap-3 p-3">
                            <a class="shrink-0" href="{{ route('parking_spot.show', $spot->id) }}">
                                <img class="h-24 w-28 rounded-md object-cover" src="{{ $spot->image_url }}" alt="駐輪場画像">
                            </a>

                            <div class="min-w-0 flex-1">
                                <a class="block truncate text-base font-bold text-slate-900 hover:text-emerald-700"
                                    href="{{ route('parking_spot.show', $spot->id) }}"
                                    data-longitude="{{ $spot->longitude }}" data-latitude="{{ $spot->latitude }}">
                                    {{ $spot->name }}
                                </a>
                                <p class="mt-1 text-sm leading-5 text-slate-600">{{ $spot->address }}</p>
                                <x-rating-summary class="mt-2" :parking-spot="$spot" />

                                <x-rate-summary class="mt-3" :parking-spot="$spot" />

                                @auth
                                    <div class="mt-3">
                                        <x-favorite-button :parking-spot="$spot" :favorited="$spot->is_favorited" compact />
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
