<div>
    @script
        <script>
            window.markers = [];
            $wire.on('displayMarkers', (spots) => {
                spots = spots[0].spots || [];

                if (markers) {
                    markers.forEach(marker => map.removeLayer(marker));
                    markers = [];
                }
                // 新しいマーカーを追加
                if (map && Array.isArray(spots)) {
                    spots.forEach(spot => {
                        if (spot.latitude && spot.longitude) {
                            marker = L.marker([spot.latitude, spot.longitude]).addTo(map).bindPopup(spot.name);
                            markers.push(marker);
                        }
                    });
                }
            });
        </script>
    @endscript
    @foreach ($spots as $spot)
        <div class="bg-gray-100 p-4">
            <div class="mx-auto max-w-xl rounded-md bg-white p-4 shadow-lg">
                <div class="flex flex-col gap-4 md:flex-row">
                    <div class="flex-shrink-0">
                        <img class="h-auto w-full rounded-md md:w-64" src="{{ $spot->image_url }}" alt="駐車場画像">
                    </div>

                    <div class="flex-1">
                        <h2 class="mb-1 text-lg font-bold text-gray-700" data-longitude="{{ $spot->longitude }}"
                            data-latitude="{{ $spot->latitude }}">
                            {{ $spot->name }}</h2>
                        <p class="mb-4 text-sm text-gray-600">{{ $spot->address }}</p>

                        <div class="flex items-center">
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-red-500">¥{{ $spot->price }}</span>
                                <span class="ml-2 text-gray-600">/</span>
                                <span class="ml-2 text-gray-600">24h</span>
                            </div>
                            <div class="ml-auto">
                                <span class="text-sm text-gray-600">{{ $spot->start_time }}</span>
                                <span class="text-sm text-gray-600">～</span>
                                <span class="text-sm text-gray-600">{{ $spot->end_time }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
