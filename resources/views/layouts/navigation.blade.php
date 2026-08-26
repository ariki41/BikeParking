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
                <div class="hidden items-center space-x-4 sm:ms-10 sm:flex">
                    <x-nav-link :href="route('parking_spot.create')" :active="request()->routeIs('parking_spot.create')">
                        駐車場追加
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('favorites.index')" :active="request()->routeIs('favorites.*')">
                            お気に入り ({{ $favoriteCount }})
                        </x-nav-link>
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            @auth
                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold leading-4 text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                マイページ
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                    ログアウト
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            @else
                <div class="hidden items-center space-x-4 sm:ms-10 sm:flex">
                    <x-nav-link :href="route('login')" :active="request()->routeIs('login')">
                        ログイン
                    </x-nav-link>
                    <x-nav-link :href="route('register')" :active="request()->routeIs('register')">
                        新規登録
                    </x-nav-link>
                </div>
            @endauth

            <div class="-me-2 flex items-center sm:hidden">
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
    <div class="hidden border-t border-slate-100 bg-white sm:hidden" :class="{ 'block': open, 'hidden': !open }">
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
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="border-t border-slate-200 pb-1 pt-4">
                <div class="px-4">
                    <div class="text-base font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        マイページ
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                            ログアウト
                        </x-responsive-nav-link>
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
