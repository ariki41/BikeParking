<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            アカウント削除
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            登録した駐輪場・料金・画像、投稿したレビュー、更新履歴は退会済みユーザーとして匿名化して残ります。あなたのお気に入りは削除されます。
        </p>
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

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                この操作は取り消せません。登録した駐輪場、投稿したレビュー、更新履歴は投稿者を特定できない状態で保持されます。あなたのお気に入りは削除され、同じユーザーIDでは再登録できません。
            </p>

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
