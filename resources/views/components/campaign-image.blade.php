@props([
    'src',
    'alt' => '',
])

@php
    $placeholder = \App\Support\Placeholder::url('landscape');
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    decoding="async"
    {{ $attributes }}
    onerror="this.onerror=null;this.src='{{ $placeholder }}';"
>
