<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            アカウント削除
        </h2>
    </header>

    <x-danger-button x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">アカウント削除</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form class="p-6" method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                本当にアカウントを削除しますか？
            </h2>

            <div class="mt-6">
                <x-input-label class="sr-only" for="password" value="'パスワード'" />

                <x-text-input class="mt-1 block w-3/4" id="password" name="password" type="password"
                    placeholder="パスワード" />

                <x-input-error class="mt-2" :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    キャンセル
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    アカウントを削除
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
