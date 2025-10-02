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

        <x-primary-button>確認画面へ進む</x-primary-button>
    </form>
</x-app-layout>
