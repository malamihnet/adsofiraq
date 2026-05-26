@props([
    'href' => null,
    'size' => 'md',
])

@php
    $href = $href ?? route('home');

    $sizes = [
        'sm' => 'height: 56px',
        'md' => 'height: 70px',
        'lg' => 'height: 70px',
    ];

    $heightStyle = $sizes[$size] ?? $sizes['md'];
    $logoUrl = url('/images/Logo-main.svg');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center']) }}>
    <img
        src="{{ $logoUrl }}"
        alt="Ads of Iraq"
        class="block w-auto max-w-[240px] md:max-w-[280px]"
        style="{{ $heightStyle }}; width: auto; max-height: none;"
        decoding="async"
        fetchpriority="high"
    >
</a>
