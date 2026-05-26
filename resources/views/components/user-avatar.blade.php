@props([
    'user',
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'h-7 w-7 text-[10px]',
        'md' => 'h-8 w-8 text-[11px]',
        'lg' => 'h-9 w-9 text-xs',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $displaySource = $user->name ?: $user->username;
    $initial = strtoupper(mb_substr($displaySource, 0, 1));
@endphp

@if($user->avatar)
    <img
        src="{{ $user->avatar_url }}"
        alt="{{ $user->name ?: $user->username }}"
        {{ $attributes->merge(['class' => "$sizeClass shrink-0 rounded-full border border-white/20 object-cover"]) }}
    >
@else
    <span {{ $attributes->merge(['class' => "inline-flex $sizeClass shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10 font-medium text-white"]) }}>
        {{ $initial }}
    </span>
@endif
