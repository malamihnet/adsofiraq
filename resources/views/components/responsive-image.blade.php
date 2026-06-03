@props([
    'src',
    'alt' => '',
    'class' => '',
    'loading' => 'lazy',
    'fetchpriority' => null,
    'width' => null,
    'height' => null,
])

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    @if($loading) loading="{{ $loading }}" @endif
    @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
    decoding="async"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $attributes->merge(['class' => trim($class)]) }}
>
