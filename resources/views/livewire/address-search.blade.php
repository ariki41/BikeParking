<div>
    <div class="mb-4">
        <x-input-label for="postalcode">郵便番号</x-input-label>
        <input class="bp-input" id="postalcode" name="postalcode" type="text"
            value="{{ old('postalcode', $postalcode) }}" wire:model.fill="postalcode" required maxlength="8"
            autocomplete="postal-code" aria-invalid="{{ $errors->has('postalcode') ? 'true' : 'false' }}"
            @if ($errors->has('postalcode')) aria-describedby="postalcode-error" @endif>
        <x-secondary-button class="mt-2" wire:click="searchAddress">住所検索</x-secondary-button>
        <x-input-error class="mt-1" id="postalcode-error" :messages="$errors->get('postalcode')" />
    </div>

    <div class="mb-4">
        <x-input-label for="address1">都道府県・市区町村・町域</x-input-label>
        <input class="bp-input bg-slate-100 text-slate-600" id="address1" name="address1" type="text"
            value="{{ old('address1', $address1) }}" required readonly placeholder="郵便番号から自動入力されます"
            aria-invalid="{{ $errors->has('address1') ? 'true' : 'false' }}"
            @if ($errors->has('address1')) aria-describedby="address1-error" @endif>
        <x-input-error class="mt-1" id="address1-error" :messages="$errors->get('address1')" />
    </div>
</div>
