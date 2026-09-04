@php
    $imagePaths = is_array($imagePaths ?? null) ? $imagePaths : [];
@endphp

<div class="md:col-span-2" data-parking-spot-images data-max-images="4">
    <x-input-label for="images">駐輪場画像（最大4枚）</x-input-label>
    <input class="peer sr-only" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
        data-image-input
        aria-describedby="images-help images-limit-error images-errors"
        aria-invalid="{{ $errors->hasAny(['images', 'images.*', 'image_paths', 'image_paths.*', 'image', 'image_path']) ? 'true' : 'false' }}">
    <label
        class="mt-2 inline-flex cursor-pointer items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2 peer-invalid:border-red-400 peer-invalid:text-red-700"
        for="images" data-image-picker>
        画像を選択
    </label>
    <p class="bp-muted mt-1" id="images-help">jpg / jpeg / png / webp、1枚あたり20MBまで選択できます。</p>

    <p class="bp-muted mt-2" data-image-count aria-live="polite">表示中の画像: {{ count($imagePaths) }} / 4枚</p>
    <p class="mt-2 hidden text-sm text-red-600" id="images-limit-error" data-image-limit-error role="alert">画像は合計4枚までです。不要な画像を削除してください。</p>

    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 {{ $imagePaths === [] ? 'hidden' : '' }}" data-image-preview-list>
        @foreach ($imagePaths as $position => $imagePath)
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white" data-image-preview-item data-image-kind="stored">
                <input name="image_paths[]" type="hidden" value="{{ $imagePath }}">
                <img class="h-28 w-full object-cover" data-image-preview
                    src="{{ \App\Models\ParkingSpot::imageUrlForPath($imagePath) }}"
                    alt="駐輪場画像プレビュー {{ $position + 1 }}">
                <div class="px-3 py-2 text-right">
                    <button class="bp-danger-link" data-delete-image type="button" aria-label="画像{{ $position + 1 }}を削除">
                        この画像を削除
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <template data-image-preview-template>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white" data-image-preview-item data-image-kind="selected">
            <img class="h-28 w-full object-cover" data-image-preview src="" alt="">
            <div class="space-y-1 px-3 py-2">
                <p class="truncate text-xs text-slate-500" data-image-name></p>
                <div class="text-right">
                    <button class="bp-danger-link" data-delete-image type="button">この画像を削除</button>
                </div>
            </div>
        </div>
    </template>

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
    </div>
</div>
