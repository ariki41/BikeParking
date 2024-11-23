<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            パスワード更新
        </h2>
    </header>

    <form class="mt-6 space-y-6" method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="'現在のパスワード'" />
            <x-text-input class="mt-1 block w-full" id="update_password_current_password" name="current_password"
                type="password" autocomplete="current-password" />
            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="'新しいパスワード'" />
            <x-text-input class="mt-1 block w-full" id="update_password_password" name="password" type="password"
                autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="'新しいパスワード再入力'" />
            <x-text-input class="mt-1 block w-full" id="update_password_password_confirmation"
                name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>保存</x-primary-button>

            @if (session('status') === 'password-updated')
                <p class="text-sm text-gray-600 dark:text-gray-400" x-data="{ show: true }" x-show="show" x-transition
                    x-init="setTimeout(() => show = false, 2000)">保存しました</p>
            @endif
        </div>
    </form>
</section>
