<x-app-layout>
    <form method="GET" action="{{ route('search') }}">
        <div class="py-12">
            <div class="flex items-center justify-center space-x-5">
                <x-text-input class="w-3/5 max-w-3xl" id="keyword" name="keyword" type="text" :value="old('keyword')" />
                <x-primary-button>検索</x-primary-button>
            </div>
        </div>
    </form>

    <section class="body-font text-gray-600">
        <div class="container mx-auto px-4 py-10">
            <div class="mb-5 flex w-full flex-col text-center">
                <h1 class="mb-1 text-left text-2xl font-medium tracking-widest text-gray-800">新着の駐車場</h1>
            </div>
            <div class="flex w-full gap-4">
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場1</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名1</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場2</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名2</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場3</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名3</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="body-font text-gray-600">
        <div class="container mx-auto px-4 py-10">
            <div class="mb-5 flex w-full flex-col text-center">
                <h1 class="mb-1 text-left text-2xl font-medium tracking-widest text-gray-800">過去に利用した駐車場</h1>
            </div>
            <div class="flex w-full gap-4">
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場1</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名1</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場2</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名2</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
                <div class="w-1/3 rounded border border-gray-400">
                    <div class="flex h-full flex-col rounded-lg bg-gray-100 p-8">
                        <div class="mb-3 flex items-center">
                            <h2 class="title-font text-lg font-medium text-gray-900">駐車場3</h2>
                        </div>
                        <div class="flex-grow">
                            <p class="text-base leading-relaxed">駐車場名3</p>
                        </div>
                        <x-primary-button class="mt-4">詳細</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
