@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center border-b-2 border-emerald-500 px-1 pt-1 text-sm font-semibold leading-5 text-slate-900 transition duration-150 ease-in-out focus:outline-none focus:border-emerald-700'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-semibold leading-5 text-slate-500 transition duration-150 ease-in-out hover:border-emerald-300 hover:text-emerald-700 focus:outline-none focus:border-emerald-300 focus:text-emerald-700';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
