<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net" rel="preconnect">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @stack('link')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $advertisingTestMode = config('advertising.test_mode');
        $adsenseClient = config('advertising.adsense.client');
        $hasConfiguredAdSlot = (request()->routeIs('home') && filled(config('advertising.adsense.slots.home_footer')))
            || (request()->routeIs('parking_spot.show') && filled(config('advertising.adsense.slots.parking_spot_footer')))
            || (request()->routeIs('search') && filled(config('advertising.adsense.slots.search_footer')));
    @endphp
    @if (config('advertising.enabled') && ! $advertisingTestMode && filled($adsenseClient) && $hasConfiguredAdSlot)
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ urlencode($adsenseClient) }}"
            crossorigin="anonymous"></script>
    @endif
    @stack('script')
</head>

<body class="font-sans antialiased">
    <div class="bp-page flex min-h-screen flex-col">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="border-b border-slate-200 bg-white/90 shadow-sm shadow-slate-200/60">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <span>サービス維持のため、ページ内の一部に広告を掲載することがあります。</span>
                <a class="font-semibold text-slate-600 hover:text-emerald-700" href="{{ route('privacy') }}">広告とプライバシー</a>
            </div>
        </footer>
    </div>
</body>

</html>
