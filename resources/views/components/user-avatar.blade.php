@props([
    'user',
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'h-7 w-7',
        'md' => 'h-8 w-8',
        'lg' => 'h-9 w-9',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<img
    src="{{ $user->avatar_url }}"
    alt="{{ $user->name ?: $user->username }}"
    {{ $attributes->merge(['class' => "$sizeClass shrink-0 rounded-full border border-white/20 object-cover"]) }}
>
