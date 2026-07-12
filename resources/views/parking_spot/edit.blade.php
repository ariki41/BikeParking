<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">駐車場の編集</h1>
            <p class="bp-muted mt-2">登録済みの内容を更新して、確認画面で差分を確認します。</p>
        </div>

        <form class="bp-panel" method="POST" action="{{ route('parking_spot.confirm') }}" enctype="multipart/form-data">
            @csrf

            <input name="id" type="hidden" value="{{ $parkingSpot['id'] }}">

            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6 p-5 sm:p-6">
                    <section>
                        <div class="mb-4">
                            <h2 class="bp-section-title">基本情報</h2>
                            <p class="bp-muted mt-1">利用者が検索結果や詳細で確認する主要情報です。</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-input-label for="name">駐車場名</x-input-label>
                                <input class="bp-input" id="name" name="name" type="text"
                                    value="{{ old('name') ?? $parkingSpot['name'] }}" required>
                                @error('name')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <livewire:address-search :postalcode="old('postalcode') ?? $postalcode" :address1="old('address1') ?? $address1" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="address2">続きの住所</x-input-label>
                                <input class="bp-input" id="address2" name="address2" type="text"
                                    value="{{ old('address2') ?? $address2 }}" required placeholder="例：1-2-3">
                                @error('address2')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="capacity">駐車場台数</x-input-label>
                                <x-select-list :name="'capacity'" :options="$capacity" :selected="old('capacity') ?? $parkingSpot['capacity']" :default="'駐車場台数を選択'" />
                                @error('capacity')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="image">駐輪場画像</x-input-label>
                                <input class="bp-input" id="image" name="image" type="file" accept="image/*">
                                <input name="image_path" type="hidden" value="{{ $imagePath }}">
                                @if ($imagePath)
                                    <img class="mt-3 h-40 w-full rounded-lg object-cover sm:w-72"
                                        src="{{ \App\Models\ParkingSpot::imageUrlForPath($imagePath) }}" alt="現在の駐輪場画像">
                                @endif
                                @error('image')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                                @error('image_path')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-4">
                            <h2 class="bp-section-title">営業時間</h2>
                            <p class="bp-muted mt-1">24時間営業の場合は開始・終了ともに00:00を指定します。</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="opening_time">開場時間</x-input-label>
                                <input class="bp-input" id="opening_time" name="opening_time" type="time"
                                    value={{ old('opening_time') ?? $parkingSpot['opening_time'] }}>
                                @error('opening_time')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="closing_time">閉場時間</x-input-label>
                                <input class="bp-input" id="closing_time" name="closing_time" type="time"
                                    value={{ old('closing_time') ?? $parkingSpot['closing_time'] }}>
                                @error('closing_time')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    @include('parking_spot.partials.rates-form')
                </div>

                <aside class="border-t border-slate-200 bg-slate-50 p-5 lg:border-l lg:border-t-0 sm:p-6">
                    <div class="sticky top-24 space-y-4">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">編集内容を確認してから更新します</p>
                            <p class="mt-2 text-sm leading-6 text-amber-800">料金や営業時間の変更は詳細画面の見え方にも反映されます。</p>
                        </div>
                        <x-primary-button class="w-full">確認画面へ進む</x-primary-button>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</x-app-layout>
