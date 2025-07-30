<x-app-layout>
    <h1 class="p-4 text-3xl">駐車場の新規登録</h1>
    <form class="mx-auto max-w-xl rounded bg-white p-6 shadow" method="POST" action="{{ route('parking_spot.confirm') }}"
        enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <x-input-label for="name">駐車場名</x-input-label>
            <input class="w-full rounded border p-2" id="name" name="name" type="text"
                value="{{ old('name') }}" required>
            @error('name')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <livewire:address-search />

        <div class="mb-4">
            <x-input-label for="address2">続きの住所</x-input-label>
            <input class="w-full rounded border p-2" id="address2" name="address2" type="text"
                value="{{ old('address2') }}" required placeholder="例：1-2-3">
            @error('name')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="capacity">駐車場台数</x-input-label>
            <x-select-list :name="'capacity'" :options="$capacity" :selected="old('capacity')" :default="'駐車場台数を選択'" />
            @error('capacity')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="opening_time">開場時間 ※24Hの場合は00:00</x-input-label>
            <input class="w-full rounded border p-2" id="opening_time" name="opening_time" type="time"
                value={{ old('opening_time', '00:00') }}>
            @error('opening_time')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="closing_time">閉場時間 ※24Hの場合は00:00</x-input-label>
            <input class="w-full rounded border p-2" id="closing_time" name="closing_time" type="time"
                value={{ old('closing_time', '00:00') }}>
            @error('closing_time')
                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <x-primary-button>登録</x-primary-button>
    </form>
</x-app-layout>
