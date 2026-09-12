<x-app-layout>
    <div class="bp-shell max-w-4xl space-y-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">マイページ</h1>
            <p class="bp-muted mt-2">アカウント情報や投稿内容を管理できます。</p>
        </div>

        @include('profile.partials.navigation')

        <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
