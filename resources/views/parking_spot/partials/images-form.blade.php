@php
    $imagePaths = is_array($imagePaths ?? null) ? $imagePaths : [];
    $deleteImagePaths = is_array(old('delete_image_paths')) ? old('delete_image_paths') : [];
@endphp

<div class="md:col-span-2">
    <x-input-label for="images">駐輪場画像（最大4枚）</x-input-label>
    <input class="bp-input" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
        aria-describedby="images-help images-errors"
        aria-invalid="{{ $errors->hasAny(['images', 'images.*', 'image_paths', 'image_paths.*', 'image', 'image_path', 'delete_image_paths', 'delete_image_paths.*']) ? 'true' : 'false' }}">
    <p class="bp-muted mt-1" id="images-help">jpg / jpeg / png / webp、1枚あたり20MBまで選択できます。</p>

    @if ($imagePaths !== [])
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($imagePaths as $position => $imagePath)
                <input name="image_paths[]" type="hidden" value="{{ $imagePath }}">
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <img class="h-28 w-full object-cover"
                        src="{{ \App\Models\ParkingSpot::imageUrlForPath($imagePath) }}"
                        alt="{{ ($isEdit ?? false) ? '現在の駐輪場画像' : 'アップロード画像プレビュー' }} {{ $position + 1 }}">
                    @if ($isEdit ?? false)
                        <label class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-slate-700"
                            for="delete-image-{{ $position }}">
                            <input class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                                id="delete-image-{{ $position }}" name="delete_image_paths[]" type="checkbox"
                                value="{{ $imagePath }}" @checked(in_array($imagePath, $deleteImagePaths, true))>
                            <span>この画像を削除</span>
                        </label>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($isEdit ?? false)
        <p class="bp-muted mt-2">新しい画像は、削除しない現在の画像の末尾に追加されます。保持する画像と追加画像の合計は最大4枚です。</p>
    @endif

    <div id="images-errors">
        <x-input-error class="mt-2" :messages="$errors->get('images')" />
        <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_paths')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_paths.*')" />
        <x-input-error class="mt-2" :messages="$errors->get('image')" />
        <x-input-error class="mt-2" :messages="$errors->get('image_path')" />
        <x-input-error class="mt-2" :messages="$errors->get('delete_image_paths')" />
        <x-input-error class="mt-2" :messages="$errors->get('delete_image_paths.*')" />
    </div>
</div>
