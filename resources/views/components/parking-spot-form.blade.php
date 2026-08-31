@props([
    'action',
    'capacity',
    'formValues',
    'imagePaths',
    'mode',
    'rateDayTypes',
    'rateUnitMinutes',
    'ratesInput',
    'parkingSpotId' => null,
])

@php
    $isEdit = $mode === 'edit';
    $values = [
        'name' => old('name', $formValues['name'] ?? ''),
        'postalcode' => old('postalcode', $formValues['postalcode'] ?? ''),
        'address1' => old('address1', $formValues['address1'] ?? ''),
        'address2' => old('address2', $formValues['address2'] ?? ''),
        'capacity' => old('capacity', $formValues['capacity'] ?? ''),
        'opening_time' => old('opening_time', $formValues['opening_time'] ?? '00:00'),
        'closing_time' => old('closing_time', $formValues['closing_time'] ?? '00:00'),
    ];

    if (session()->hasOldInput('rates') && is_array(old('rates'))) {
        $ratesInput = old('rates');
    }

    if (session()->hasOldInput('image_paths') && is_array(old('image_paths'))) {
        $imagePaths = old('image_paths');
    } elseif (session()->hasOldInput('image_path') && filled(old('image_path'))) {
        $imagePaths = [old('image_path')];
    }

    $pageTitle = $isEdit ? '駐車場の編集' : '駐車場の新規登録';
    $pageDescription = $isEdit
        ? '登録済みの内容を更新して、確認画面で差分を確認します。'
        : '住所・営業時間・料金帯を入力して、登録内容を確認します。';
    $basicDescription = $isEdit
        ? '利用者が検索結果や詳細で確認する主要情報です。'
        : '検索結果や詳細画面に表示される情報です。';
    $noticeClasses = $isEdit
        ? 'border-amber-200 bg-amber-50 text-amber-900'
        : 'border-emerald-200 bg-emerald-50 text-emerald-800';
    $noticeDetailClasses = $isEdit ? 'text-amber-800' : 'text-emerald-700';
@endphp

<div class="bp-shell">
    <x-input-error class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3" :messages="$errors->get('confirmation')" />

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $pageTitle }}</h1>
            <p class="bp-muted mt-2">{{ $pageDescription }}</p>
        </div>
    </div>

    <form class="bp-panel" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf

        @if ($isEdit)
            <input name="id" type="hidden" value="{{ $parkingSpotId }}">
        @endif

        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6 p-5 sm:p-6">
                <section aria-labelledby="parking-spot-basic-heading">
                    <div class="mb-4">
                        <h2 class="bp-section-title" id="parking-spot-basic-heading">基本情報</h2>
                        <p class="bp-muted mt-1">{{ $basicDescription }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="name">駐車場名</x-input-label>
                            <input class="bp-input" id="name" name="name" type="text" value="{{ $values['name'] }}"
                                required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                @if ($errors->has('name')) aria-describedby="name-error" @endif>
                            <x-input-error class="mt-1" id="name-error" :messages="$errors->get('name')" />
                        </div>

                        <div class="md:col-span-2">
                            <livewire:address-search :postalcode="$values['postalcode']" :address1="$values['address1']" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="address2">続きの住所</x-input-label>
                            <input class="bp-input" id="address2" name="address2" type="text"
                                value="{{ $values['address2'] }}" required autocomplete="address-line2" placeholder="例：1-2-3"
                                aria-invalid="{{ $errors->has('address2') ? 'true' : 'false' }}"
                                @if ($errors->has('address2')) aria-describedby="address2-error" @endif>
                            <x-input-error class="mt-1" id="address2-error" :messages="$errors->get('address2')" />
                        </div>

                        <div>
                            <x-input-label for="capacity">駐車場台数</x-input-label>
                            <select class="bp-select" id="capacity" name="capacity" required
                                aria-invalid="{{ $errors->has('capacity') ? 'true' : 'false' }}"
                                @if ($errors->has('capacity')) aria-describedby="capacity-error" @endif>
                                <option value="" disabled @selected($values['capacity'] === '' || $values['capacity'] === null)>駐車場台数を選択</option>
                                @foreach ($capacity as $key => $label)
                                    <option value="{{ $key }}" @selected((string) $values['capacity'] === (string) $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1" id="capacity-error" :messages="$errors->get('capacity')" />
                        </div>

                        @include('parking_spot.partials.images-form', ['isEdit' => $isEdit])
                    </div>
                </section>

                <section aria-labelledby="parking-spot-hours-heading">
                    <div class="mb-4">
                        <h2 class="bp-section-title" id="parking-spot-hours-heading">営業時間</h2>
                        <p class="bp-muted mt-1">24時間営業の場合は開始・終了ともに00:00を指定します。</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="opening_time">開場時間</x-input-label>
                            <input class="bp-input" id="opening_time" name="opening_time" type="time"
                                value="{{ $values['opening_time'] }}" required
                                aria-invalid="{{ $errors->has('opening_time') ? 'true' : 'false' }}"
                                @if ($errors->has('opening_time')) aria-describedby="opening-time-error" @endif>
                            <x-input-error class="mt-1" id="opening-time-error" :messages="$errors->get('opening_time')" />
                        </div>

                        <div>
                            <x-input-label for="closing_time">閉場時間</x-input-label>
                            <input class="bp-input" id="closing_time" name="closing_time" type="time"
                                value="{{ $values['closing_time'] }}" required
                                aria-invalid="{{ $errors->has('closing_time') ? 'true' : 'false' }}"
                                @if ($errors->has('closing_time')) aria-describedby="closing-time-error" @endif>
                            <x-input-error class="mt-1" id="closing-time-error" :messages="$errors->get('closing_time')" />
                        </div>
                    </div>
                </section>

                @include('parking_spot.partials.rates-form')
            </div>

            <aside class="border-t border-slate-200 bg-slate-50 p-5 lg:border-l lg:border-t-0 sm:p-6">
                <div class="sticky top-24 space-y-4">
                    <div class="rounded-lg border p-4 {{ $noticeClasses }}">
                        <p class="text-sm font-semibold">
                            {{ $isEdit ? '編集内容を確認してから更新します' : '入力後に確認画面へ進みます' }}
                        </p>
                        <p class="mt-2 text-sm leading-6 {{ $noticeDetailClasses }}">
                            {{ $isEdit ? '料金や営業時間の変更は詳細画面の見え方にも反映されます。' : '料金帯は最大4件まで登録できます。平日・土日祝など、利用者が迷わない区分で分けてください。' }}
                        </p>
                    </div>
                    <x-primary-button class="w-full">確認画面へ進む</x-primary-button>
                </div>
            </aside>
        </div>
    </form>
</div>
