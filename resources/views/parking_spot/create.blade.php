<x-app-layout>
    <div class="bp-shell">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">駐車場の新規登録</h1>
                <p class="bp-muted mt-2">住所・営業時間・料金帯を入力して、登録内容を確認します。</p>
            </div>
        </div>

        <form class="bp-panel" method="POST" action="{{ route('parking_spot.confirm') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6 p-5 sm:p-6">
                    <section>
                        <div class="mb-4">
                            <h2 class="bp-section-title">基本情報</h2>
                            <p class="bp-muted mt-1">検索結果や詳細画面に表示される情報です。</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-input-label for="name">駐車場名</x-input-label>
                                <input class="bp-input" id="name" name="name" type="text" value="{{ old('name') }}"
                                    required>
                                @error('name')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <livewire:address-search />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="address2">続きの住所</x-input-label>
                                <input class="bp-input" id="address2" name="address2" type="text"
                                    value="{{ old('address2') }}" required placeholder="例：1-2-3">
                                @error('address2')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="capacity">駐車場台数</x-input-label>
                                <x-select-list :name="'capacity'" :options="$capacity" :selected="old('capacity')" :default="'駐車場台数を選択'" />
                                @error('capacity')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="image">駐輪場画像</x-input-label>
                                <input class="bp-input" id="image" name="image" type="file" accept="image/*">
                                <input name="image_path" type="hidden" value="{{ old('image_path') }}">
                                @if (old('image_path'))
                                    <img class="mt-3 h-40 w-full rounded-lg object-cover sm:w-72"
                                        src="{{ \App\Models\ParkingSpot::imageUrlForPath(old('image_path')) }}" alt="アップロード画像プレビュー">
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
                                    value={{ old('opening_time', '00:00') }}>
                                @error('opening_time')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="closing_time">閉場時間</x-input-label>
                                <input class="bp-input" id="closing_time" name="closing_time" type="time"
                                    value={{ old('closing_time', '00:00') }}>
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
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-sm font-semibold text-emerald-800">入力後に確認画面へ進みます</p>
                            <p class="mt-2 text-sm leading-6 text-emerald-700">料金帯は最大4件まで登録できます。平日・土日祝など、利用者が迷わない区分で分けてください。</p>
                        </div>
                        <x-primary-button class="w-full">確認画面へ進む</x-primary-button>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</x-app-layout>
