<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-md border border-transparent bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-900/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:bg-emerald-800 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
