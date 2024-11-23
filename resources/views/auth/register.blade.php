<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- ユーザー名 -->
        <div>
            <x-input-label for="name" :value="'ユーザー名'" />
            <x-text-input class="mt-1 block w-full" id="name" name="name" type="text" :value="old('name')" required
                autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- ユーザーID -->
        <div class="mt-4">
            <x-input-label for="userid" :value="'ユーザーID'" />
            <x-text-input class="mt-1 block w-full" id="userid" name="userid" type="text" :value="old('userid')"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('userid')" />
        </div>

        <!-- パスワード -->
        <div class="mt-4">
            <x-input-label for="password" :value="'パスワード'" />

            <x-text-input class="mt-1 block w-full" id="password" name="password" type="password" required
                autocomplete="new-password" />

            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <!-- パスワード再入力 -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'パスワード再入力'" />

            <x-text-input class="mt-1 block w-full" id="password_confirmation" name="password_confirmation"
                type="password" required autocomplete="new-password" />

            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <a class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                href="{{ route('login') }}">
                登録済みの方はこちら
            </a>

            <x-primary-button class="ms-4">
                登録
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
