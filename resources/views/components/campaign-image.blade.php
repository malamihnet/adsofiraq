@props([
    'src',
    'alt' => '',
])

@php
    $placeholder = placeholderUrl('landscape');
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    {{ $attributes }}
    onerror="this.onerror=null;this.src='{{ $placeholder }}';"
>
