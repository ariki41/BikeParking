<x-app-layout>
    <div
        class="grid min-h-[calc(100vh-4rem)] grid-cols-1 bg-stone-50 lg:h-full lg:min-h-0 lg:grid-cols-[420px_minmax(0,1fr)]">
        <div
            class="border-b border-slate-200 bg-white p-4 lg:flex lg:min-h-0 lg:flex-col lg:border-b-0 lg:border-r">
            <form class="lg:shrink-0" method="GET" action="{{ route('search') }}">
                <div class="space-y-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">駐輪場を探す</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-text-input class="w-full" id="keyword" name="keyword" type="text"
                            placeholder="駅名・地名を入力" :value="$keyword" />
                        <x-primary-button class="shrink-0">検索</x-primary-button>
                    </div>
                    <div>
                        <x-input-label for="engine_displacement">駐車したいバイクの排気量</x-input-label>
                        <select class="bp-select mt-1" id="engine_displacement" name="engine_displacement">
                            <option value="">指定なし</option>
                            @foreach ($displacementClasses as $displacementClass)
                                <option value="{{ $displacementClass->value }}"
                                    @selected($engineDisplacement === $displacementClass->value)>
                                    {{ $displacementClass->searchLabel() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
