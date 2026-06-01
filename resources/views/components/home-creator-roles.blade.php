@props(['roles' => []])

@php
    $labels = array_values(array_filter($roles));
    $primary = $labels[0] ?? null;
    $extra = max(0, count($labels) - 1);
@endphp

@if($primary)
    <p class="mt-1 w-full max-w-full text-center text-[10px] font-normal leading-snug text-archive-gray line-clamp-2 max-md:text-[9px]">
        <span class="uppercase tracking-wide">{{ $primary }}</span>
        @if($extra > 0)
            <span class="normal-case text-archive-gray/80"> +{{ $extra }}</span>
        @endif
    </p>
@endif
