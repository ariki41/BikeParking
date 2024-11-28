<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- ユーザーID -->
        <div>
            <x-input-label for="user_id" :value="'ユーザーID'" />
            <x-text-input class="mt-1 block w-full" id="user_id" name="user_id" type="text" :value="old('user_id')" required
                autofocus autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
        </div>

        <!-- パスワード -->
        <div class="mt-4">
            <x-input-label for="password" :value="'パスワード'" />

            <x-text-input class="mt-1 block w-full" id="password" name="password" type="password" required
                autocomplete="current-password" />

            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <!-- ログイン状態を保持する -->
        <div class="mt-4 block">
            <label class="inline-flex items-center" for="remember_me">
                <input
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    id="remember_me" name="remember" type="checkbox">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">ログイン状態を保持する</span>
            </label>
        </div>

        <div class="mt-4 flex items-center justify-end">
            <x-primary-button class="ms-3">
                ログイン
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
