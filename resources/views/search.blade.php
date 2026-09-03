<x-app-layout>
    <div
        class="grid min-h-[calc(100vh-4rem)] grid-cols-1 bg-stone-50 lg:h-full lg:min-h-0 lg:grid-cols-[420px_minmax(0,1fr)]">
        <div
            class="border-b border-slate-200 bg-white p-4 lg:flex lg:min-h-0 lg:flex-col lg:border-b-0 lg:border-r">
            <form class="lg:shrink-0" method="GET" action="{{ route('search') }}" x-data>
                <div class="space-y-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">駐輪場を探す</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-text-input class="w-full" id="keyword" name="keyword" type="text"
                            placeholder="駅名・地名を入力" :value="$keyword" />
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
                                        'lat' => $yolpLocation['lat'],
                                        'lon' => $yolpLocation['lon'],
                                    ], fn ($value) => $value !== null && $value !== '')) }}"
                                    aria-label="排気量条件をクリア">
                                    クリア
                                </a>
                            @endif
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($displacementClasses as $displacementClass)
                                <label class="cursor-pointer">
                                    <input class="peer sr-only" name="engine_displacement" type="radio"
                                        value="{{ $displacementClass->value }}"
                                        @checked($engineDisplacement === $displacementClass->value)
                                        x-on:change="$el.form.requestSubmit()">
                                    <span
                                        class="flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-center text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                                        {{ $displacementClass->searchLabel() }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @if (session('error'))
                        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                            <p class="text-sm text-red-600">{{ session('error') }}</p>
                        </div>
                    @endif
                </div>
                <input name="lat" type="hidden" value="{{ $yolpLocation['lat'] }}">
                <input name="lon" type="hidden" value="{{ $yolpLocation['lon'] }}">
            </form>
            @if (session('favorite_success'))
                <p
                    class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 lg:shrink-0">
                    {{ session('favorite_success') }}
                </p>
            @endif
            <div
                class="mt-5 h-[calc(100vh-17rem)] overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 lg:h-auto lg:min-h-0 lg:flex-1"
                id="parking-spots">
                @livewire('parking-spots', ['engineDisplacement' => $engineDisplacement])
            </div>
            <x-ad-slot class="mt-4 lg:shrink-0" placement="search_footer" />
        </div>

        <x-leaflet-map class="h-[60vh] min-h-96 bg-slate-100 lg:h-full lg:min-h-0"
            :latitude="$yolpLocation['lat']" :longitude="$yolpLocation['lon']" :zoom="15"
            bounds-event="updateBounds" markers-event="displayMarkers"
            :marker-url-template="route('parking_spot.show', ['parkingSpot' => '__ID__'])" />
    </div>
</x-app-layout>
