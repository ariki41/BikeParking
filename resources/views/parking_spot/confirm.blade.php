<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">登録内容の確認</h1>
            <p class="bp-muted mt-2">内容に問題がなければ登録・更新します。</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div class="bp-panel">
                <div class="grid gap-1 overflow-hidden border-b border-slate-100 sm:grid-cols-2">
                    @forelse ($validatedData['image_paths'] ?? [] as $position => $imagePath)
                        <img class="h-56 w-full object-cover"
                            src="{{ \App\Models\ParkingSpot::imageUrlForPath($imagePath) }}"
                            alt="駐輪場画像 {{ $position + 1 }}">
                    @empty
                        <img class="h-72 w-full object-cover sm:col-span-2"
                            src="{{ \App\Models\ParkingSpot::imageUrlForPath(null) }}"
                            alt="駐輪場画像未設定">
                    @endforelse
                </div>
                <div class="bp-panel-header">
                    <h2 class="bp-section-title">{{ $validatedData['name'] ?? '' }}</h2>
                    <p class="bp-muted mt-1">{{ $validatedData['address'] ?? '' }}</p>
                </div>

                <dl class="grid gap-0 divide-y divide-slate-100 p-5 text-sm">
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4 sm:first:pt-0">
                        <dt class="font-semibold text-slate-500">郵便番号</dt>
                        <dd class="text-slate-900">
                            {{ substr($validatedData['postalcode'], 0, 3) . '-' . substr($validatedData['postalcode'], 3, 4) ?? '' }}
                        </dd>
                    </div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4">
                        <dt class="font-semibold text-slate-500">収容台数</dt>
                        <dd class="text-slate-900">{{ $capacity[$validatedData['capacity']] ?? '' }}</dd>
                    </div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4">
                        <dt class="font-semibold text-slate-500">画像</dt>
                        <dd class="text-slate-900">
                            {{ count($validatedData['image_paths'] ?? []) > 0 ? count($validatedData['image_paths']) . '枚' : '未設定' }}
                        </dd>
                    </div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4">
                        <dt class="font-semibold text-slate-500">営業時間</dt>
                        <dd class="text-slate-900">
                            {{ $validatedData['opening_time'] ?? '' }} ～
                            {{ $validatedData['closing_time'] === '00:00' ? '24:00' : $validatedData['closing_time'] ?? '' }}
                        </dd>
                    </div>
                    <div class="grid gap-3 py-3 sm:grid-cols-[140px_1fr] sm:gap-4 sm:last:pb-0">
                        <dt class="font-semibold text-slate-500">料金</dt>
                        <dd class="space-y-3 text-slate-900">
                            @foreach ($validatedData['rates'] as $rate)
                                @php
                                    $unitMinutes = (int) ($rate['unit_minutes'] ?? 0);
                                    $freeMinutes = (int) ($rate['free_minutes'] ?? 0);
                                    $unitLabel = $unitMinutes >= 60 && $unitMinutes % 60 === 0 ? $unitMinutes / 60 . '時間' : $unitMinutes . '分';
                                    $freeLabel = $freeMinutes >= 60 && $freeMinutes % 60 === 0 ? $freeMinutes / 60 . '時間' : $freeMinutes . '分';
                                @endphp
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="bp-badge">{{ $rate['day_type'] ?? '' }}</span>
                                        <span class="text-sm font-semibold text-slate-700">
                                            {{ \App\Models\ParkingSpotRates::formatTimeRange($rate['start_time'] ?? null, $rate['end_time'] ?? null) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-700">
                                        @if ($freeMinutes > 0)
                                            最初の{{ $freeLabel }}無料 /
                                        @endif
                                        {{ $unitLabel }} {{ number_format($rate['rate'] ?? 0) }}円
                                        @if ($rate['no_max_rate'] ?? false)
                                            / 最大料金なし
                                        @elseif (($rate['max_rate'] ?? null) !== null && $rate['max_rate'] !== '')
                                            / 最大 {{ number_format($rate['max_rate']) }}円
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                </dl>

                <form class="border-t border-slate-100 p-5" id="parkingSpotConfirmForm" method="POST"
                    action="{{ $validatedData['id'] ? route('parking_spot.update', ['parkingSpot' => $validatedData['id']]) : route('parking_spot.store') }}">
                    @csrf
                    @if ($validatedData['id'])
                        @method('PUT')
                    @endif
                    <div class="flex flex-wrap gap-3">
                        @if ($validatedData['id'])
                            <x-primary-button>更新</x-primary-button>
                            <x-secondary-button name="back" type="submit" value="back">戻る</x-secondary-button>
                        @else
                            <x-primary-button>登録</x-primary-button>
                            <x-secondary-button name="back" type="submit" value="back">戻る</x-secondary-button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bp-panel">
                <x-leaflet-map class="h-[28rem] w-full bg-slate-100" :latitude="$validatedData['latitude']"
                    :longitude="$validatedData['longitude']" :zoom="18" :markers="[[
                        'latitude' => $validatedData['latitude'],
                        'longitude' => $validatedData['longitude'],
                    ]]" />
            </div>
        </div>
    </div>
</x-app-layout>
