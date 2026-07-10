<div class="mb-4 border-t pt-4" id="parking-spot-rates">
    <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">料金</h2>
        <button class="rounded bg-gray-800 px-3 py-2 text-sm font-semibold text-white" id="add-rate-button"
            type="button">
            料金帯を追加
        </button>
    </div>

    @error('rates')
        <div class="mb-4 text-sm text-red-600">{{ $message }}</div>
    @enderror
    @php
        $rateErrors = collect($errors->getMessages())->filter(fn ($messages, $field) => str_starts_with($field, 'rates.'));
    @endphp
    @if ($rateErrors->isNotEmpty())
        <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-600">
            @foreach ($rateErrors as $messages)
                @foreach ($messages as $message)
                    <div>{{ $message }}</div>
                @endforeach
            @endforeach
        </div>
    @endif

    <div id="rate-list">
        @foreach ($ratesInput as $index => $rate)
            <div class="rate-item mb-4 rounded border border-gray-200 p-4">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-gray-700">料金帯<span class="rate-number">{{ $index + 1 }}</span></h3>
                    <button class="delete-rate-button text-sm font-semibold text-red-600" type="button">
                        削除
                    </button>
                </div>

                <div class="mb-4">
                    <x-input-label>料金区分</x-input-label>
                    <select
                        class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                        data-rate-field="day_type" name="rates[{{ $index }}][day_type]">
                        @foreach ($rateDayTypes as $key => $value)
                            <option value="{{ $key }}" @selected(($rate['day_type'] ?? '') === $key)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>料金開始時間</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="start_time"
                            name="rates[{{ $index }}][start_time]" type="time"
                            value="{{ $rate['start_time'] ?? '00:00' }}" required>
                    </div>

                    <div>
                        <x-input-label>料金終了時間</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="end_time"
                            name="rates[{{ $index }}][end_time]" type="time"
                            value="{{ $rate['end_time'] ?? '00:00' }}" required>
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>料金単位（分）</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="unit_minutes"
                            name="rates[{{ $index }}][unit_minutes]" type="number"
                            value="{{ $rate['unit_minutes'] ?? 30 }}" min="1" required placeholder="例：30">
                    </div>

                    <div>
                        <x-input-label>料金（円）</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="rate"
                            name="rates[{{ $index }}][rate]" type="number" value="{{ $rate['rate'] ?? '' }}"
                            min="0" required placeholder="例：100">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label>最初の無料時間（分）</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="free_minutes"
                            name="rates[{{ $index }}][free_minutes]" type="number"
                            value="{{ $rate['free_minutes'] ?? 0 }}" min="0" placeholder="例：30">
                    </div>

                    <div>
                        <x-input-label>最大料金（円）</x-input-label>
                        <input class="w-full rounded border p-2" data-rate-field="max_rate"
                            name="rates[{{ $index }}][max_rate]" type="number"
                            value="{{ $rate['max_rate'] ?? '' }}" min="0" placeholder="例：1200">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template id="rate-template">
        <div class="rate-item mb-4 rounded border border-gray-200 p-4">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h3 class="font-semibold text-gray-700">料金帯<span class="rate-number"></span></h3>
                <button class="delete-rate-button text-sm font-semibold text-red-600" type="button">
                    削除
                </button>
            </div>

            <div class="mb-4">
                <x-input-label>料金区分</x-input-label>
                <select
                    class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    data-rate-field="day_type">
                    @foreach ($rateDayTypes as $key => $value)
                        <option value="{{ $key }}" @selected($key === '全日')>{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>料金開始時間</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="start_time" type="time" value="00:00"
                        required>
                </div>

                <div>
                    <x-input-label>料金終了時間</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="end_time" type="time" value="00:00"
                        required>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>料金単位（分）</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="unit_minutes" type="number"
                        value="30" min="1" required placeholder="例：30">
                </div>

                <div>
                    <x-input-label>料金（円）</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="rate" type="number" min="0"
                        required placeholder="例：100">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label>最初の無料時間（分）</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="free_minutes" type="number"
                        value="0" min="0" placeholder="例：30">
                </div>

                <div>
                    <x-input-label>最大料金（円）</x-input-label>
                    <input class="w-full rounded border p-2" data-rate-field="max_rate" type="number" min="0"
                        placeholder="例：1200">
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

                const renumberRates = () => {
                    const items = list.querySelectorAll('.rate-item');

                    items.forEach((item, index) => {
                        item.querySelector('.rate-number').textContent = index + 1;
                        item.querySelectorAll('[data-rate-field]').forEach((field) => {
                            field.name = `rates[${index}][${field.dataset.rateField}]`;
                        });
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

                renumberRates();
            });
        </script>
    @endpush
@endonce
