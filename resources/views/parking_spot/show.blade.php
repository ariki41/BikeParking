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
            window.map = L.map('map').setView([{{ $parkingSpot->latitude }}, {{ $parkingSpot->longitude }}],
                17);

            // OpenStreetMapタイルレイヤーを追加
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            L.marker([{{ $parkingSpot->latitude }}, {{ $parkingSpot->longitude }}]).addTo(map)
        }
    </script>
@endpush

<x-app-layout>
    <div class="container mx-auto space-y-6 p-4">
        <h1 class="text-3xl font-bold text-gray-800">
            {{ $parkingSpot->name }}
        </h1>

        <div class="mx-7 grid grid-cols-1 gap-4 xl:grid-cols-5">
            <div class="xl:col-span-5">
                <img class="h-64 w-full rounded-lg object-cover shadow" src="/images/parking-sample.jpg" alt="駐車場の写真">
            </div>

            <div class="h-96 w-full rounded bg-gray-200 xl:col-span-3" id="map">
            </div>

            <div class="rounded-lg bg-white p-4 shadow xl:col-span-2">
                <table class="w-full table-auto">
                    <tbody>
                        <tr class="border-b">
                            <th class="w-1/3 px-4 py-2 text-left text-sm font-semibold text-gray-600">駐車場名</th>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $parkingSpot->name }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-1/3 px-4 py-2 text-left text-sm font-semibold text-gray-600">住所</th>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $parkingSpot->address }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-1/3 px-4 py-2 text-left text-sm font-semibold text-gray-600">料金</th>
                            <td class="px-4 py-2 text-sm text-gray-800"></td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-1/3 px-4 py-2 text-left text-sm font-semibold text-gray-600">営業時間</th>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $parkingSpot->opening_time }} ～
                                {{ $parkingSpot->closing_time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
