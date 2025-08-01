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
            window.map = L.map('map').setView([{{ $validatedData['latitude'] }}, {{ $validatedData['longitude'] }}],
                18);

            // マーカーを追加
            L.marker([{{ $validatedData['latitude'] }}, {{ $validatedData['longitude'] }}]).addTo(map);

            // OpenStreetMapタイルレイヤーを追加
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }
    </script>
    <script>
        function setFormAction(actionUrl) {
            const form = document.getElementById('parkingSpotConfirmForm');
            form.action = actionUrl;
        }
    </script>
@endpush

<x-app-layout>
    <div class="m-5 grid grid-cols-2">
        <div class="col-span-2 mx-auto mb-4 max-w-lg rounded bg-white p-6 shadow lg:col-span-1">
            <table class="mb-4 min-w-full divide-y divide-gray-200 border">
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            駐車場名
                        </th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{{ $validatedData['name'] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            郵便番号
                        </th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                            {{ substr($validatedData['postalcode'], 0, 3) . '-' . substr($validatedData['postalcode'], 3, 4) ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            住所
                        </th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                            {{ $validatedData['address'] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            収容台数</th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                            {{ $capacity[$validatedData['capacity']] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            開場時間</th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                            {{ $validatedData['opening_time'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th
                            class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                            閉場時間</th>
                        <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                            {{ $validatedData['closing_time'] ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="flex gap-4">
                <form id="parkingSpotConfirmForm" method="POST" action="{{ route('parking_spot.store') }}">
                    @csrf
                    @foreach ($validatedData as $key => $value)
                        <input name="{{ $key }}" type="hidden" value="{{ $value }}">
                    @endforeach
                    <div class="flex gap-4">
                        <x-primary-button type="submit"
                            onclick="setFormAction('{{ route('parking_spot.store') }}')">登録</x-primary-button>
                        <x-primary-button type="submit"
                            onclick="setFormAction('{{ route('parking_spot.create_back') }}')">戻る</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-span-2 h-96 lg:col-span-1" id="map">
        </div>
    </div>
</x-app-layout>
