@props([
    'id' => 'map',
    'latitude',
    'longitude',
    'zoom' => 15,
    'markers' => [],
    'boundsEvent' => null,
    'markersEvent' => null,
    'markerUrlTemplate' => null,
    'errorMessage' => '地図を読み込めませんでした。時間をおいて再度お試しください。',
])

@php
    $configuration = [
        'center' => [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ],
        'zoom' => (int) $zoom,
        'markers' => array_values($markers),
        'boundsEvent' => $boundsEvent,
        'markersEvent' => $markersEvent,
        'markerUrlTemplate' => $markerUrlTemplate,
    ];
@endphp

@once
    @push('link')
        <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush

    @push('script')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush
@endonce

<div {{ $attributes->class(['relative']) }} data-leaflet-map-root>
    <div class="h-full w-full" id="{{ $id }}" data-leaflet-map data-leaflet-configuration='@json($configuration)'></div>
    <p class="absolute inset-0 hidden items-center justify-center bg-slate-100 px-6 text-center text-sm text-slate-600"
        data-leaflet-map-error role="alert">
        {{ $errorMessage }}
    </p>
</div>
