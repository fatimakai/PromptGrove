@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-amber-50 px-4 py-2 text-start text-base font-semibold text-amber-900'
            : 'block w-full rounded-lg px-4 py-2 text-start text-base font-medium text-gray-700 hover:bg-amber-50';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
