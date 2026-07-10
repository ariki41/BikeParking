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

        <div class="mx-7 space-y-4">
            <div>
                <img class="h-64 rounded-lg object-cover shadow" src="/images/noimage.jpg" alt="駐車場の写真">
            </div>

            <div class="h-96 w-full rounded bg-gray-200" id="map">
            </div>

            <div class="min-w-0 rounded-lg bg-white p-4 shadow">
                <table class="w-full table-fixed">
                    <tbody>
                        <tr class="border-b">
                            <th class="w-24 px-3 py-2 text-left text-sm font-semibold text-gray-600 xl:w-28">駐車場名</th>
                            <td class="break-words px-3 py-2 text-sm text-gray-800">{{ $parkingSpot->name }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-24 px-3 py-2 text-left text-sm font-semibold text-gray-600 xl:w-28">住所</th>
                            <td class="break-words px-3 py-2 text-sm text-gray-800">{{ $parkingSpot->address }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-24 px-3 py-2 text-left text-sm font-semibold text-gray-600 xl:w-28">料金</th>
                            <td class="min-w-0 px-3 py-2 text-sm text-gray-800">
                                @if ($parkingSpot->rates->isEmpty())
                                    <span class="text-gray-500">料金未登録</span>
                                @else
                                    <div class="max-w-full overflow-x-auto">
                                        <table class="w-max min-w-full table-auto border-collapse text-left text-sm">
                                            <thead>
                                                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-600">
                                                    <th class="whitespace-nowrap px-3 py-2">区分</th>
                                                    <th class="whitespace-nowrap px-3 py-2">時間帯</th>
                                                    <th class="whitespace-nowrap px-3 py-2">料金</th>
                                                    <th class="whitespace-nowrap px-3 py-2">最大料金</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($parkingSpot->rates as $rate)
                                                    <tr>
                                                        <td class="whitespace-nowrap px-3 py-2 font-semibold text-gray-700">
                                                            {{ $rate->day_type }}
                                                        </td>
                                                        <td class="whitespace-nowrap px-3 py-2">
                                                            {{ $rate->time_range_label }}
                                                        </td>
                                                        <td class="min-w-40 px-3 py-2">
                                                            {{ $rate->base_rate_label }}
                                                        </td>
                                                        <td class="whitespace-nowrap px-3 py-2">
                                                            {{ $rate->max_rate_label }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-24 px-3 py-2 text-left text-sm font-semibold text-gray-600 xl:w-28">駐車場台数</th>
                            <td class="px-3 py-2 text-sm text-gray-800">{{ $parkingSpot->capacity }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="w-24 px-3 py-2 text-left text-sm font-semibold text-gray-600 xl:w-28">営業時間</th>
                            <td class="px-3 py-2 text-sm text-gray-800">{{ $parkingSpot->opening_time }} ～
                                {{ $parkingSpot->closing_time }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-5 text-xs text-gray-500">
                    <p>作成日: {{ optional($parkingSpot->created_at)->format('Y-m-d') }}</p>
                    <p>更新日: {{ optional($parkingSpot->updated_at)->format('Y-m-d') }}</p>
                </div>
            </div>
            <div class="flex justify-end">
                <a href="{{ route('parking_spot.edit', ['id' => $parkingSpot->id]) }}">
                    <x-primary-button tag="a">編集</x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
