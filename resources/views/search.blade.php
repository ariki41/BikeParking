<x-app-layout>
    <div
        class="grid min-h-[calc(100vh-4rem)] grid-cols-1 bg-stone-50 lg:h-full lg:min-h-0 lg:grid-cols-[420px_minmax(0,1fr)]">
        <div
            class="border-b border-slate-200 bg-white p-4 lg:flex lg:min-h-0 lg:flex-col lg:border-b-0 lg:border-r">
            @livewire('parking-spots', [
                'keyword' => $keyword,
                'latitude' => $yolpLocation['lat'],
                'longitude' => $yolpLocation['lon'],
                'engineDisplacement' => $engineDisplacement,
                'zoom' => $zoom,
            ])
            <x-ad-slot class="mt-4 lg:shrink-0" placement="search_footer" />
        </div>

        <x-leaflet-map class="h-[60vh] min-h-96 bg-slate-100 lg:h-full lg:min-h-0"
            :latitude="$yolpLocation['lat']" :longitude="$yolpLocation['lon']" :zoom="$zoom"
            bounds-event="updateBounds" markers-event="displayMarkers"
            :marker-url-template="route('parking_spot.show', ['parkingSpot' => '__ID__'])" />
    </div>
</x-app-layout>
