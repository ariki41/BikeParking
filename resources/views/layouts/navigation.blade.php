@auth
    @php($favoriteCount = Auth::user()->favorites()->count())
@endauth

<nav class="sticky top-0 z-[1100] shrink-0 border-b border-slate-200 bg-white/95 shadow-sm shadow-slate-200/60 backdrop-blur"
    x-data="{ open: false }">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a class="flex items-center gap-3" href="{{ route('home') }}">
                        <x-application-logo class="h-12 w-12 rounded-md object-contain" />
                        <span class="sr-only">BikeParking</span>
                    </a>
                </div>
                <div class="hidden items-center space-x-4 lg:ms-10 lg:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        ホーム
                    </x-nav-link>
                    <x-nav-link :href="route('parking_spot.create')" :active="request()->routeIs('parking_spot.create')">
                        駐車場追加
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('favorites.index')" :active="request()->routeIs('favorites.*')">
                            お気に入り ({{ $favoriteCount }})
                        </x-nav-link>
                        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                            マイページ
                        </x-nav-link>
                    @endauth
                </div>
            </div>

            <!-- User Information and Authentication -->
            @auth
                <div class="hidden items-center gap-4 lg:ms-6 lg:flex">
                    <span class="max-w-40 truncate text-sm font-semibold text-slate-700" aria-label="ログイン中のユーザー"
                        title="{{ Auth::user()->name }}">
                        {{ Auth::user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold leading-4 text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                            type="submit">
                            ログアウト
                        </button>
                    </form>
                </div>
            @else
                <div class="hidden items-center space-x-4 lg:ms-10 lg:flex">
                    <x-nav-link :href="route('login')" :active="request()->routeIs('login')">
                        ログイン
                    </x-nav-link>
                    <x-nav-link :href="route('register')" :active="request()->routeIs('register')">
                        新規登録
                    </x-nav-link>
                </div>
            @endauth

            <div class="-me-2 flex items-center lg:hidden">
                <button
                    class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    type="button" @click="open = ! open">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div class="hidden border-t border-slate-100 bg-white lg:hidden" :class="{ 'block': open, 'hidden': !open }">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                ホーム
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('parking_spot.create')" :active="request()->routeIs('parking_spot.create')">
                駐車場追加
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('favorites.index')" :active="request()->routeIs('favorites.*')">
                    お気に入り ({{ $favoriteCount }})
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                    マイページ
                </x-responsive-nav-link>
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="border-t border-slate-200 pb-1 pt-4">
                <div class="px-4">
                    <div class="text-base font-semibold text-slate-800" aria-label="ログイン中のユーザー">
                        {{ Auth::user()->name }}
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            class="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-semibold text-slate-600 transition duration-150 ease-in-out hover:border-emerald-300 hover:bg-slate-50 hover:text-emerald-700 focus:outline-none focus:bg-slate-50 focus:text-emerald-700"
                            type="submit">
                            ログアウト
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="border-t border-slate-200 pb-3 pt-3">
                <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                    ログイン
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                    新規登録
                </x-responsive-nav-link>
            </div>
        @endauth
    </div>
</nav>
