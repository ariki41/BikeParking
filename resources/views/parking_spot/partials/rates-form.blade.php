<div class="border-t border-slate-200 pt-6" id="parking-spot-rates">
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <h2 class="bp-section-title">料金</h2>
            <p class="bp-muted mt-1">時間帯ごとの料金を最大4件まで設定できます。</p>
        </div>
        <button
            class="inline-flex items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
            id="add-rate-button" type="button">
            料金帯を追加
        </button>
    </div>

    @error('rates')
        <div class="mb-4 text-sm text-red-600">{{ $message }}</div>
    @enderror

    <div id="rate-list">
        @foreach ($ratesInput as $index => $rate)
            <div class="rate-item mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-slate-800">料金帯<span class="rate-number">{{ $index + 1 }}</span></h3>
                    <button class="delete-rate-button bp-danger-link" type="button">
                        削除
                    </button>
                </div>

                @php
                    $itemErrors = collect($errors->getMessages())->filter(
                        fn($messages, $field) => str_starts_with($field, "rates.{$index}."),
                    );
                @endphp
                @if ($itemErrors->isNotEmpty())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600">
                        @foreach ($itemErrors as $messages)
                            @foreach ($messages as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        @endforeach
                    </div>
                @endif

                <div class="mb-4">
                    <x-input-label>料金区分</x-input-label>
                    <select
                        class="bp-select"
                        name="rates[{{ $index }}][day_type]" data-rate-field="day_type">
                        @foreach ($rateDayTypes as $key => $value)
                            <option value="{{ $key }}" @selected(($rate['day_type'] ?? '') === $key)>{{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>料金開始時間</x-input-label>
                        <input class="bp-input" name="rates[{{ $index }}][start_time]"
                            data-rate-field="start_time" type="time" value="{{ $rate['start_time'] ?? '00:00' }}"
                            required>
                    </div>

                    <div>
                        <x-input-label>料金終了時間</x-input-label>
                        <input class="bp-input" name="rates[{{ $index }}][end_time]"
                            data-rate-field="end_time" type="time" value="{{ $rate['end_time'] ?? '00:00' }}"
                            required>
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>料金単位</x-input-label>
                        <select class="bp-select" name="rates[{{ $index }}][unit_minutes]"
                            data-rate-field="unit_minutes" required>
                            @foreach ($rateUnitMinutes as $minutes => $label)
                                <option value="{{ $minutes }}" @selected((int) ($rate['unit_minutes'] ?? 30) === $minutes)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label>料金（円）</x-input-label>
                        <input class="bp-input" name="rates[{{ $index }}][rate]"
                            data-rate-field="rate" type="number" value="{{ $rate['rate'] ?? '' }}" min="0"
                            required placeholder="例：100">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>最初の無料時間（分）</x-input-label>
                        <input class="free-minutes-input bp-input" name="rates[{{ $index }}][free_minutes]"
                            data-rate-field="free_minutes" type="number" value="{{ $rate['free_minutes'] ?? 0 }}"
                            min="0" placeholder="例：30">
                        <label
                            class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <input
                                class="no-free-minutes-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                                name="rates[{{ $index }}][no_free_minutes]" data-rate-field="no_free_minutes"
                                type="checkbox" value="1" @checked($rate['no_free_minutes'] ?? ((int) ($rate['free_minutes'] ?? 0) === 0))>
                            <span>無料時間なし</span>
                        </label>
                    </div>

                    <div>
                        <x-input-label>最大料金（円）</x-input-label>
                        <input class="max-rate-input bp-input"
                            name="rates[{{ $index }}][max_rate]" data-rate-field="max_rate" type="number"
                            value="{{ $rate['max_rate'] ?? '' }}" min="1" placeholder="例：1200">
                        <label
                            class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <input
                                class="no-max-rate-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                                name="rates[{{ $index }}][no_max_rate]" data-rate-field="no_max_rate"
                                type="checkbox" value="1" @checked($rate['no_max_rate'] ?? false)>
                            <span>最大料金なし</span>
                        </label>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template id="rate-template">
        <div class="rate-item mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h3 class="font-semibold text-slate-800">料金帯<span class="rate-number"></span></h3>
                <button class="delete-rate-button bp-danger-link" type="button">
                    削除
                </button>
            </div>

            <div class="mb-4">
                <x-input-label>料金区分</x-input-label>
                <select
                    class="bp-select"
                    data-rate-field="day_type">
                    @foreach ($rateDayTypes as $key => $value)
                        <option value="{{ $key }}" @selected($key === '全日')>{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>料金開始時間</x-input-label>
                    <input class="bp-input" data-rate-field="start_time" type="time" value="00:00"
                        required>
                </div>

                <div>
                    <x-input-label>料金終了時間</x-input-label>
                    <input class="bp-input" data-rate-field="end_time" type="time" value="00:00"
                        required>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>料金単位（分）</x-input-label>
                    <select class="bp-select" data-rate-field="unit_minutes" required>
                        @foreach ($rateUnitMinutes as $minutes => $label)
                            <option value="{{ $minutes }}" @selected($minutes === 30)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label>料金（円）</x-input-label>
                    <input class="bp-input" data-rate-field="rate" type="number" min="0"
                        required placeholder="例：100">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>最初の無料時間（分）</x-input-label>
                    <input class="free-minutes-input bp-input" data-rate-field="free_minutes" type="number"
                        value="0" min="0" placeholder="例：30">
                    <label
                        class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input
                            class="no-free-minutes-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                            data-rate-field="no_free_minutes" type="checkbox" value="1" checked>
                        <span>無料時間なし</span>
                    </label>
                </div>

                <div>
                    <x-input-label>最大料金（円）</x-input-label>
                    <input class="max-rate-input bp-input" data-rate-field="max_rate" type="number"
                        min="1" placeholder="例：1200">
                    <label
                        class="mt-2 flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input
                            class="no-max-rate-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                            data-rate-field="no_max_rate" type="checkbox" value="1">
                        <span>最大料金なし</span>
                    </label>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const root = document.getElementById('parking-spot-rates');
                if (!root) {
                    return;
                }

                const list = root.querySelector('#rate-list');
                const template = root.querySelector('#rate-template');
                const addButton = root.querySelector('#add-rate-button');
                const maxRates = 4;

                const syncFreeMinutesState = (item) => {
                    const checkbox = item.querySelector('.no-free-minutes-checkbox');
                    const input = item.querySelector('.free-minutes-input');

                    if (!checkbox || !input) {
                        return;
                    }

                    input.readOnly = checkbox.checked;
                    input.classList.toggle('bg-slate-100', checkbox.checked);
                    input.classList.toggle('text-slate-500', checkbox.checked);
                    input.classList.toggle('cursor-not-allowed', checkbox.checked);
                    input.classList.toggle('bg-white', !checkbox.checked);

                    if (checkbox.checked) {
                        input.value = '0';
                    }
                };

                const syncMaxRateState = (item) => {
                    const checkbox = item.querySelector('.no-max-rate-checkbox');
                    const input = item.querySelector('.max-rate-input');

                    if (!checkbox || !input) {
                        return;
                    }

                    input.disabled = checkbox.checked;
                    input.classList.toggle('border-slate-300', checkbox.checked);
                    input.classList.toggle('bg-slate-100', checkbox.checked);
                    input.classList.toggle('text-slate-500', checkbox.checked);
                    input.classList.toggle('cursor-not-allowed', checkbox.checked);
                    input.classList.toggle('border-slate-300', !checkbox.checked);
                    input.classList.toggle('bg-white', !checkbox.checked);

                    if (checkbox.checked) {
                        input.value = '';
                    }
                };

                const renumberRates = () => {
                    const items = list.querySelectorAll('.rate-item');

                    items.forEach((item, index) => {
                        item.querySelector('.rate-number').textContent = index + 1;
                        item.querySelectorAll('[data-rate-field]').forEach((field) => {
                            field.name = `rates[${index}][${field.dataset.rateField}]`;
                        });
                        syncFreeMinutesState(item);
                        syncMaxRateState(item);
                    });

                    list.querySelectorAll('.delete-rate-button').forEach((button) => {
                        button.classList.toggle('hidden', items.length <= 1);
                    });

                    addButton.classList.toggle('hidden', items.length >= maxRates);
                };

                addButton.addEventListener('click', () => {
                    if (list.querySelectorAll('.rate-item').length >= maxRates) {
                        return;
                    }

                    list.appendChild(template.content.firstElementChild.cloneNode(true));
                    renumberRates();
                });

                list.addEventListener('click', (event) => {
                    if (!event.target.classList.contains('delete-rate-button')) {
                        return;
                    }

                    event.target.closest('.rate-item').remove();
                    renumberRates();
                });

                list.addEventListener('change', (event) => {
                    if (!event.target.classList.contains('no-max-rate-checkbox') &&
                        !event.target.classList.contains('no-free-minutes-checkbox')) {
                        return;
                    }

                    const item = event.target.closest('.rate-item');
                    syncFreeMinutesState(item);
                    syncMaxRateState(item);
                });

                renumberRates();
            });
        </script>
    @endpush
@endonce
