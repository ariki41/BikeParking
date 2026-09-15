<x-app-layout>
    <x-parking-spot-form action="{{ route('parking_spot.confirm') }}" :capacity="$capacity"
        :displacement-classes="$displacementClasses" :form-values="$formValues" :image-paths="$imagePaths"
        mode="edit" :parking-spot-id="$parkingSpot->id"
        :rate-day-types="$rateDayTypes" :rate-unit-minutes="$rateUnitMinutes" :rates-input="$ratesInput" :business-hour-day-types="$businessHourDayTypes" :business-hours-input="$businessHoursInput" />

    <section class="bp-shell mt-8 max-w-4xl">
        @if (session('status'))
            <p class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="bp-panel p-5">
            <h2 class="text-lg font-bold text-slate-900">施設の状態を変更</h2>
            <p class="mt-2 text-sm text-slate-600">閉鎖済みの場合は検索結果に状態を表示したまま掲載停止にします。誤登録は管理者が確認してから削除します。</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <h3 class="font-semibold text-slate-800">閉鎖済み</h3>
                    <p class="mt-1 text-sm text-slate-600">検索結果には閉鎖済みと表示されます。料金・画像・お気に入り・レビュー・更新履歴は保持します。</p>
                    <button class="mt-3 inline-flex min-h-10 items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" type="button" x-data="" x-on:click="$dispatch('open-modal', 'confirm-close')">閉鎖済みとして掲載停止</button>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">誤登録</h3>
                    <p class="mt-1 text-sm text-slate-600">理由を添えて申請すると、管理者が確認してから削除します。</p>
                    <button class="mt-3 inline-flex min-h-10 items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" type="button" x-data="" x-on:click="$dispatch('open-modal', 'confirm-deletion-request')">削除を申請する</button>
                </div>
            </div>
        </div>
    </section>

    <x-modal name="confirm-close" focusable>
        <form class="p-6" method="POST" action="{{ route('parking_spot.close', $parkingSpot) }}">
            @csrf
            <h2 class="text-lg font-bold text-slate-900">閉鎖済みとして掲載停止しますか？</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">駐輪場は検索結果で「閉鎖済み」と表示されます。料金・画像・お気に入り・レビュー・更新履歴は削除されません。</p>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'confirm-close')">キャンセル</x-secondary-button>
                <x-danger-button>掲載停止する</x-danger-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="confirm-deletion-request" :show="$errors->has('reason')" focusable>
        <form class="p-6" method="POST" action="{{ route('parking_spot.deletion_requests.store', $parkingSpot) }}">
            @csrf
            <h2 class="text-lg font-bold text-slate-900">誤登録として削除を申請しますか？</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">この時点では削除されません。管理者が内容を確認し、理由を記録してから削除します。</p>
            <div class="mt-5">
                <x-input-label for="deletion-reason" value="誤登録の理由" />
                <textarea class="bp-input mt-2 min-h-28" id="deletion-reason" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('reason')" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'confirm-deletion-request')">キャンセル</x-secondary-button>
                <x-danger-button>削除を申請する</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
