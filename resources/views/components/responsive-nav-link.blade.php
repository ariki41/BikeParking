@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'block w-full border-l-4 border-emerald-500 bg-emerald-50 py-2 pe-4 ps-3 text-start text-base font-semibold text-emerald-700 transition duration-150 ease-in-out focus:outline-none focus:bg-emerald-100 focus:text-emerald-800'
            : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-semibold text-slate-600 transition duration-150 ease-in-out hover:border-emerald-300 hover:bg-slate-50 hover:text-emerald-700 focus:outline-none focus:bg-slate-50 focus:text-emerald-700';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
