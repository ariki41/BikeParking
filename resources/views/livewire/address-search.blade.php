<div>
    <div class="mb-4">
        <x-input-label for="postalcode">郵便番号</x-input-label>
        <input class="w-full rounded border p-2" id="postalcode" name="postalcode" type="text"
            value="{{ old('postalcode') ?? $postalcode }}" wire:model.fill='postalcode' required length="7">
        <x-secondary-button class="mt-2" wire:click="searchAddress">住所検索</x-secondary-button>
        @error('postalcode')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <x-input-label for="address1">都道府県・市区町村・町域</x-input-label>
        <input class="w-full rounded border bg-gray-200 p-2" id="address1" name="address1" type="text"
            value="{{ old('address1') ?? $address1 }}" required readonly placeholder="郵便番号から自動入力されます">
        @error('address1')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>
