<x-app-layout>
    <h1 class="p-4 text-3xl">駐車場の編集</h1>
    <form class="mx-auto max-w-xl rounded bg-white p-6 shadow" method="POST" action="{{ route('parking_spot.confirm') }}"
        enctype="multipart/form-data">
        @csrf

        <input name="id" type="hidden" value="{{ $parkingSpot['id'] }}">

        <div class="mb-4">
            <x-input-label for="name">駐車場名</x-input-label>
            <input class="w-full rounded border p-2" id="name" name="name" type="text"
                value="{{ old('name') ?? $parkingSpot['name'] }}" required>
            @error('name')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <livewire:address-search :postalcode="old('postalcode') ?? $postalcode" :address1="old('address1') ?? $address1" />

        <div class="mb-4">
            <x-input-label for="address2">続きの住所</x-input-label>
            <input class="w-full rounded border p-2" id="address2" name="address2" type="text"
                value="{{ old('address2') ?? $address2 }}" required placeholder="例：1-2-3">
            @error('address2')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="capacity">駐車場台数</x-input-label>
            <x-select-list :name="'capacity'" :options="$capacity" :selected="old('capacity') ?? $parkingSpot['capacity']" :default="'駐車場台数を選択'" />
            @error('capacity')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="opening_time">開場時間 ※24Hの場合は00:00</x-input-label>
            <input class="w-full rounded border p-2" id="opening_time" name="opening_time" type="time"
                value={{ old('opening_time') ?? $parkingSpot['opening_time'] }}>
            @error('opening_time')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="closing_time">閉場時間 ※24Hの場合は00:00</x-input-label>
            <input class="w-full rounded border p-2" id="closing_time" name="closing_time" type="time"
                value={{ old('closing_time') ?? $parkingSpot['closing_time'] }}>
            @error('closing_time')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4 border-t pt-4">
            <h2 class="mb-4 text-xl font-semibold text-gray-800">料金</h2>

            <div class="mb-4">
                <x-input-label for="rate_day_type">料金区分</x-input-label>
                <x-select-list :name="'rate_day_type'" :options="$rateDayTypes" :selected="old('rate_day_type') ?? optional($rate)->day_type ?? '全日'" :default="'料金区分を選択'" />
                @error('rate_day_type')
                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rate_start_time">料金開始時間</x-input-label>
                    <input class="w-full rounded border p-2" id="rate_start_time" name="rate_start_time" type="time"
                        value="{{ old('rate_start_time') ?? (optional($rate)->start_time ? date('H:i', strtotime($rate->start_time)) : '00:00') }}"
                        required>
                    @error('rate_start_time')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-input-label for="rate_end_time">料金終了時間</x-input-label>
                    <input class="w-full rounded border p-2" id="rate_end_time" name="rate_end_time" type="time"
                        value="{{ old('rate_end_time') ?? (optional($rate)->end_time ? date('H:i', strtotime($rate->end_time)) : '00:00') }}"
                        required>
                    @error('rate_end_time')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="unit_minutes">料金単位（分）</x-input-label>
                    <input class="w-full rounded border p-2" id="unit_minutes" name="unit_minutes" type="number"
                        value="{{ old('unit_minutes') ?? optional($rate)->unit_minutes ?? 30 }}" min="1" required
                        placeholder="例：30">
                    @error('unit_minutes')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-input-label for="rate">料金（円）</x-input-label>
                    <input class="w-full rounded border p-2" id="rate" name="rate" type="number"
                        value="{{ old('rate') ?? optional($rate)->rate }}" min="0" required placeholder="例：100">
                    @error('rate')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="free_minutes">最初の無料時間（分）</x-input-label>
                    <input class="w-full rounded border p-2" id="free_minutes" name="free_minutes" type="number"
                        value="{{ old('free_minutes') ?? optional($rate)->free_minutes ?? 0 }}" min="0"
                        placeholder="例：30">
                    @error('free_minutes')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-input-label for="max_rate">最大料金（円）</x-input-label>
                    <input class="w-full rounded border p-2" id="max_rate" name="max_rate" type="number"
                        value="{{ old('max_rate') ?? optional($rate)->max_rate }}" min="0" placeholder="例：1200">
                    @error('max_rate')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <x-primary-button>確認画面へ進む</x-primary-button>
    </form>
</x-app-layout>
