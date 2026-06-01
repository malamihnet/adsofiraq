@props([
    'href',
    'imageUrl',
    'name',
    'verified' => false,
    'subtitle' => null,
    'meta' => null,
    'imageSize' => 'agency',
])

@php
    $avatarSizeClass = match ($imageSize) {
        'person' => 'h-11 w-11 md:h-16 md:w-16',
        default => 'h-14 w-14 md:h-[4.5rem] md:w-[4.5rem]',
    };
@endphp

<a
    href="{{ $href }}"
    class="group flex h-full flex-col items-center rounded-lg border border-archive-border/80 bg-white px-2 py-3 text-center transition-colors hover:border-archive-black md:p-4"
>
    <div class="{{ $avatarSizeClass }} aspect-square shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light">
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            loading="lazy"
        >
    </div>

    <div class="mt-2 w-full min-w-0 md:mt-3">
        <h3 class="font-display text-xs leading-snug text-archive-black line-clamp-2 group-hover:underline md:text-sm lg:text-[15px]">
            <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
                <span class="min-w-0">{{ $name }}</span>
                <x-verified-badge :verified="$verified" class="shrink-0" />
            </span>
        </h3>

        @if($subtitle)
            <p class="mt-1 text-[10px] leading-snug text-archive-gray line-clamp-2 md:mt-1.5 md:text-[11px]">
                {{ $subtitle }}
            </p>
        @endif

        @if(isset($badges))
            <div class="mt-1.5 flex justify-center md:mt-2">
                {{ $badges }}
            </div>
        @endif

        @if($meta)
            <p class="mt-1 text-[10px] text-archive-gray md:mt-1.5 md:text-[11px]">{{ $meta }}</p>
        @endif
    </div>
</a>
