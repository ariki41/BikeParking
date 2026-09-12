<x-app-layout>
    <div class="bp-shell max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">駐輪場を通報</h1>
            <p class="mt-2 text-sm text-slate-600">「{{ $parkingSpot->name }}」について、確認が必要な内容を運営へ知らせます。</p>
        </div>

        <div class="bp-panel p-5">
            <form class="space-y-5" method="POST" action="{{ route('parking_spot.reports.store', $parkingSpot) }}">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="parking_spot_update_history_id">対象</label>
                    <select class="bp-select mt-2" id="parking_spot_update_history_id" name="parking_spot_update_history_id">
                        <option value="">駐輪場全体</option>
                        @foreach ($parkingSpot->updateHistories as $history)
                            <option value="{{ $history->id }}" @selected((int) old('parking_spot_update_history_id') === $history->id)>{{ $history->created_at?->format('Y-m-d H:i') }}: {{ $history->change_summary }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('parking_spot_update_history_id')" />
                </div>
                <div>
                    <x-input-label for="reason" value="通報理由" />
                    <textarea class="bp-input mt-2 min-h-32" id="reason" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button>通報する</x-primary-button>
                    <a class="text-sm font-semibold text-slate-600 hover:text-slate-900" href="{{ route('parking_spot.show', $parkingSpot) }}">詳細へ戻る</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
