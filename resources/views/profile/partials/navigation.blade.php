<nav class="border-b border-gray-200 dark:border-gray-700" aria-label="マイページ">
    <div class="flex gap-6">
        <a class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('profile.edit') ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}" href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.edit')) aria-current="page" @endif>
            アカウント設定
        </a>
        <a class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('profile.reviews') ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}" href="{{ route('profile.reviews') }}" @if (request()->routeIs('profile.reviews')) aria-current="page" @endif>
            投稿したレビュー
        </a>
        <a class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('profile.images') ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}" href="{{ route('profile.images') }}" @if (request()->routeIs('profile.images')) aria-current="page" @endif>
            投稿した画像
        </a>
    </div>
</nav>
