@props([
    'src',
    'alt' => '',
])

@php
    $placeholder = asset(config('upload.placeholder', 'images/placeholder.webp'));
    if (! file_exists(public_path(config('upload.placeholder', 'images/placeholder.webp')))) {
        $placeholder = asset(config('upload.placeholder_fallback', 'images/placeholder.jpg'));
    }
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    {{ $attributes }}
    onerror="this.onerror=null;this.src='{{ $placeholder }}';"
>
