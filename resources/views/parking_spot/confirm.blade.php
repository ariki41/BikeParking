<x-app-layout>
    <h1 class="p-4 text-3xl">確認画面</h1>
    <div class="mx-auto max-w-lg rounded bg-white p-6 shadow">
        <table class="mb-5 min-w-full divide-y divide-gray-200 border">
            <tbody class="divide-y divide-gray-200 bg-white">
                <tr>
                    <th class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                        駐車場名
                    </th>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{{ $validatedData['name'] ?? '' }}</td>
                </tr>
                <tr>
                    <th class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                        郵便番号
                    </th>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                        {{ substr($validatedData['postalcode'], 0, 3) . '-' . substr($validatedData['postalcode'], 3, 4) ?? '' }}
                    </td>
                </tr>
                <tr>
                    <th class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">住所
                    </th>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{{ $validatedData['address'] ?? '' }}
                    </td>
                </tr>
                <tr>
                    <th class="w-1/4 whitespace-nowrap bg-gray-50 px-6 py-3 text-left text-sm font-bold text-gray-700">
                        収容台数</th>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-900">
                        {{ $capacity[$validatedData['capacity']] ?? '' }}</td>
                </tr>
            </tbody>
        </table>
        <div class="flex gap-4">
            <form method="POST" action="{{ route('parking_spot.store') }}">
                @csrf
                @foreach ($validatedData as $key => $value)
                    <input name="{{ $key }}" type="hidden" value="{{ $value }}">
                @endforeach
                <div class="flex gap-4">
                    <x-primary-button type="submit">登録</x-primary-button>
                </div>
            </form>
            <form method="POST" action="{{ route('parking_spot.create_back') }}">
                @csrf
                @foreach ($validatedData as $key => $value)
                    <input name="{{ $key }}" type="hidden" value="{{ $value }}">
                @endforeach
                <div class="flex gap-4">
                    <x-primary-button type="submit">戻る</x-primary-button>
                </div>
            </form>

        </div>
</x-app-layout>
