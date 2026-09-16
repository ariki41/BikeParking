<x-app-layout title="駐輪場情報を確認" robots="noindex, nofollow">
    <div class="bp-shell">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">登録内容の確認</h1>
            <p class="bp-muted mt-2">内容に問題がなければ登録・更新します。</p>
        </div>

        @if ($duplicateCandidates->isNotEmpty())
            <section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="duplicate-candidates-heading">
                <h2 class="text-lg font-bold text-amber-950" id="duplicate-candidates-heading">重複している可能性がある駐輪場</h2>
                <p class="mt-1 text-sm text-amber-900">同じ住所、または200m以内で名称が類似する施設が見つかりました。別施設であることを確認したうえで、登録を続けられます。</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($duplicateCandidates as $candidate)
                        <li>
                            <a class="font-semibold text-sky-700 underline hover:text-sky-900" href="{{ route('parking_spot.show', $candidate) }}" target="_blank" rel="noopener noreferrer">{{ $candidate->name }}</a>
                            <span class="text-sm text-amber-950">（{{ $candidate->address }}）</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

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
                        <dt class="font-semibold text-slate-500">駐車可能な排気量</dt>
                        <dd class="text-slate-900">{{ $displacementClass->label() }}</dd>
                    </div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4">
                        <dt class="font-semibold text-slate-500">画像</dt>
                        <dd class="text-slate-900">
                            {{ count($validatedData['image_paths'] ?? []) > 0 ? count($validatedData['image_paths']) . '枚' : '未設定' }}
                        </dd>
                    </div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[140px_1fr] sm:gap-4">
                        <dt class="font-semibold text-slate-500">営業時間</dt>
                        <dd class="space-y-1 text-slate-900">@foreach ($validatedData['business_hours'] as $hour)<div>{{ $hour['day_type'] }}: @if ($hour['is_closed'] ?? false) {{ $hour['opening_time'] === '00:00' && $hour['closing_time'] === '00:00' ? '終日休業' : $hour['opening_time'].' ～ '.($hour['closing_time'] === '00:00' ? '翌0:00' : $hour['closing_time']).' 休業' }} @else {{ $hour['opening_time'] === '00:00' && $hour['closing_time'] === '00:00' ? '24時間営業' : $hour['opening_time'].' ～ '.($hour['closing_time'] === '00:00' ? '翌0:00' : $hour['closing_time']) }} @endif</div>@endforeach</dd>
                    </div>
                    <div class="grid gap-3 py-3 sm:grid-cols-[140px_1fr] sm:gap-4 sm:last:pb-0">
                        <dt class="font-semibold text-slate-500">料金</dt>
                        <dd class="space-y-3 text-slate-900">
                            @foreach ($rateDisplays as $rate)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="bp-badge">{{ $rate->dayType }}</span>
                                        <span class="text-sm font-semibold text-slate-700">
                                            {{ $rate->timeRangeLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-700">
                                        {{ $rate->rateLabel }}
                                    </p>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                </dl>

                <form class="border-t border-slate-100 p-5" id="parkingSpotConfirmForm" method="POST"
                    action="{{ $validatedData['id'] ? route('parking_spot.update', ['parkingSpot' => $validatedData['id']]) : route('parking_spot.store') }}">
                    @csrf
                    <input id="parking-spot-confirm-latitude" name="latitude" type="hidden" value="{{ $validatedData['latitude'] }}">
                    <input id="parking-spot-confirm-longitude" name="longitude" type="hidden" value="{{ $validatedData['longitude'] }}">
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
                <div class="border-b border-slate-100 p-5">
                    <h2 class="bp-section-title">駐輪場の位置</h2>
                    <p class="bp-muted mt-1">マーカーをドラッグして、入口など実際の位置に合わせたら「位置を反映」を押してください。</p>
                </div>
                <x-leaflet-map id="parking-spot-confirm-map" class="h-[28rem] w-full bg-slate-100" :latitude="$validatedData['latitude']"
                    :longitude="$validatedData['longitude']" :zoom="18" :markers="[[
                        'latitude' => $validatedData['latitude'],
                        'longitude' => $validatedData['longitude'],
                    ]]" draggable-marker marker-latitude-input-id="parking-spot-marker-latitude"
                    marker-longitude-input-id="parking-spot-marker-longitude" />
                <form class="border-t border-slate-100 p-5" method="POST" action="{{ route('parking_spot.confirm.location') }}"
                    data-location-correction data-latitude-input-id="parking-spot-marker-latitude"
                    data-longitude-input-id="parking-spot-marker-longitude"
                    data-confirmed-latitude-input-id="parking-spot-confirm-latitude"
                    data-confirmed-longitude-input-id="parking-spot-confirm-longitude">
                    @csrf
                    <input id="parking-spot-marker-latitude" name="latitude" type="hidden" value="{{ $validatedData['latitude'] }}">
                    <input id="parking-spot-marker-longitude" name="longitude" type="hidden" value="{{ $validatedData['longitude'] }}">
                    <button class="inline-flex min-h-10 items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60" type="submit">位置を反映</button>
                    <p class="bp-muted mt-2 text-sm" data-location-correction-status aria-live="polite"></p>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
