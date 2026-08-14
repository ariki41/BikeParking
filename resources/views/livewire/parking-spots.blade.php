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
            <article class="bp-card-link overflow-hidden">
                <div class="flex gap-3 p-3">
                    <a class="shrink-0" href="{{ route('parking_spot.show', $spot->id) }}">
                        <img class="h-24 w-28 rounded-md object-cover" src="{{ $spot->image_url }}" alt="駐車場画像">
                    </a>

                    <div class="min-w-0 flex-1">
                        <a class="block truncate text-base font-bold text-slate-900 hover:text-emerald-700"
                            href="{{ route('parking_spot.show', $spot->id) }}" data-longitude="{{ $spot->longitude }}"
                            data-latitude="{{ $spot->latitude }}">
                            {{ $spot->name }}
                        </a>
                        <p class="mt-1 text-sm leading-5 text-slate-600">{{ $spot->address }}</p>
                        <x-rating-summary class="mt-2" :parking-spot="$spot" />

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($rate)
                                <span class="text-lg font-bold text-emerald-700">{{ $rate->rate_label }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $rate->day_type }}</span>
                                <span class="text-xs text-slate-500">{{ $rate->time_range_label }}</span>
                            @else
                                <span class="text-sm font-semibold text-slate-500">料金未登録</span>
                            @endif
                        </div>

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
</div>
