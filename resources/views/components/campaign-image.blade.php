@props([
    'src',
    'alt' => '',
    'loading' => 'lazy',
])

@php
    $placeholder = \App\Support\Placeholder::url('landscape');
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    decoding="async"
    @if($loading) loading="{{ $loading }}" @endif
    {{ $attributes }}
    onerror="this.onerror=null;this.src='{{ $placeholder }}';"
>
