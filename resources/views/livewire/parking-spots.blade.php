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
    <div class="space-y-3 p-3">
        @foreach ($spots as $spot)
            @php
                $rate = $spot->rates->first();
            @endphp
            <a class="bp-card-link" href="{{ route('parking_spot.show', $spot->id) }}">
                <div class="flex gap-3 p-3">
                    <img class="h-24 w-28 shrink-0 rounded-md object-cover" src="{{ $spot->image_url }}" alt="駐車場画像">

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-bold text-slate-900" data-longitude="{{ $spot->longitude }}"
                            data-latitude="{{ $spot->latitude }}">
                            {{ $spot->name }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-600">{{ $spot->address }}</p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($rate)
                                <span class="text-lg font-bold text-emerald-700">{{ $rate->rate_label }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $rate->day_type }}</span>
                                <span class="text-xs text-slate-500">{{ $rate->time_range_label }}</span>
                            @else
                                <span class="text-sm font-semibold text-slate-500">料金未登録</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
