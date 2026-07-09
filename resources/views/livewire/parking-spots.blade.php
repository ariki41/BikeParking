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
                            const popupContent =
                                `<a href="/parking-spot/detail/${spot.id}">${spot.name}</a>`;
                            marker = L.marker([spot.latitude, spot.longitude]).addTo(map).bindPopup(
                                popupContent);
                            markers.push(marker);
                        }
                    });
                }
            });
        </script>
    @endscript
    @foreach ($spots as $spot)
        @php
            $rate = $spot->rates->first();
        @endphp
        <a href="{{ route('parking_spot.show', $spot->id) }}">
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
                                @if ($rate)
                                    <div class="flex items-center">
                                        <span class="text-2xl font-bold text-red-500">{{ number_format($rate->rate) }}円</span>
                                        <span class="ml-2 text-gray-600">/</span>
                                        <span class="ml-2 text-gray-600">{{ $rate->day_type }}</span>
                                    </div>
                                    <div class="ml-auto">
                                        <span class="text-sm text-gray-600">{{ date('H:i', strtotime($rate->start_time)) }}</span>
                                        <span class="text-sm text-gray-600">～</span>
                                        <span class="text-sm text-gray-600">{{ $rate->end_time === '00:00:00' ? '24:00' : date('H:i', strtotime($rate->end_time)) }}</span>
                                    </div>
                                @else
                                    <span class="text-sm font-semibold text-gray-500">料金未登録</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    @endforeach
</div>
