<x-app-layout>
    <form method="GET" action="{{ route('search') }}">
        <div class="py-12">
            <div class="flex items-center justify-center space-x-5">
                <x-text-input class="w-3/5 max-w-3xl" id="keyword" name="keyword" type="text" :value="old('keyword')" />
                <x-primary-button>検索</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
