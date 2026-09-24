@props(['active'])

@php
$classes = ($active ?? false)
            ? 'grove-nav-link is-active'
            : 'grove-nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
