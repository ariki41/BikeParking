@php
    $imagePaths = is_array($imagePaths ?? null) ? $imagePaths : [];
@endphp

<div class="md:col-span-2">
    <x-input-label for="images">駐輪場画像（最大4枚）</x-input-label>
    <input class="bp-input" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
        aria-describedby="images-help images-errors"
        aria-invalid="{{ $errors->hasAny(['images', 'images.*', 'image_paths', 'image_paths.*', 'image', 'image_path']) ? 'true' : 'false' }}">
    <p class="bp-muted mt-1" id="images-help">jpg / jpeg / png / webp、1枚あたり20MBまで選択できます。</p>

    @if ($imagePaths !== [])
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($imagePaths as $position => $imagePath)
                <input name="image_paths[]" type="hidden" value="{{ $imagePath }}">
                <img class="h-28 w-full rounded-lg object-cover"
                    src="{{ \App\Models\ParkingSpot::imageUrlForPath($imagePath) }}"
                    alt="{{ ($isEdit ?? false) ? '現在の駐輪場画像' : 'アップロード画像プレビュー' }} {{ $position + 1 }}">
            @endforeach
        </div>
    @endif

    @if ($isEdit ?? false)
        <p class="bp-muted mt-2">新しい画像を選択すると、現在の画像を選択した画像へすべて置き換えます。</p>
    @endif

    <div id="images-errors">
        <x-input-error class="mt-2" :messages="$errors->get('images')" />
        <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_paths')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_paths.*')" />
        <x-input-error class="mt-2" :messages="$errors->get('image')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_path')" />
    </div>
</div>
