@push('link')
    <!-- LeafletのCSS -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('script')
    <!-- LeafletのJavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // 地図を初期化
        window.onload = function() {
            window.map = L.map('map').setView([{{ $yolpLocation['lat'] }}, {{ $yolpLocation['lon'] }}],
                15);

            // OpenStreetMapタイルレイヤーを追加
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // 地図が読み込まれた後に、表示されている範囲のマーカーを表示する
            map.whenReady(function() {
                getBoundsMap();
            });

            // 地図を動かしたときに、表示されている範囲のマーカーを更新する
            map.on('moveend', function() {
                getBoundsMap();
            });
        }

        // 表示されている範囲を取得して、Livewireに送信する
        function getBoundsMap() {
            const bound = map.getBounds();
            const bounds = {
                north: bound.getNorth(),
                east: bound.getEast(),
                south: bound.getSouth(),
                west: bound.getWest()
            };
            Livewire.dispatch('updateBounds', {
                bounds
            });
        }
    </script>
@endpush

<x-app-layout>
    <div class="grid min-h-[calc(100vh-4rem)] grid-cols-1 bg-stone-50 lg:grid-cols-[420px_minmax(0,1fr)]">
        <div class="border-b border-slate-200 bg-white p-4 lg:border-b-0 lg:border-r">
            <form method="GET" action="{{ route('search') }}">
                <div class="space-y-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">駐輪場を探す</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-text-input class="w-full" id="keyword" name="keyword" type="text"
                            placeholder="駅名・地名を入力" :value="$keyword" />
                        <x-primary-button class="shrink-0">検索</x-primary-button>
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
                <p class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                    {{ session('favorite_success') }}
                </p>
            @endif
            <div class="mt-5 h-[calc(100vh-17rem)] overflow-y-auto rounded-lg border border-slate-200 bg-slate-50" id="parking-spots">
                @livewire('parking-spots')
            </div>
        </div>

        <div class="h-[60vh] min-h-96 bg-slate-100 lg:h-[calc(100vh-4rem)]" id="map"></div>
    </div>
</x-app-layout>
