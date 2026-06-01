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
    $avatarClass = match ($imageSize) {
        'person' => 'h-10 w-10 md:h-16 md:w-16',
        default => 'h-14 w-14 md:h-[72px] md:w-[72px]',
    };
@endphp

<a
    href="{{ $href }}"
    class="group flex h-full flex-col items-center rounded-lg border border-archive-border/80 bg-white text-center transition-colors hover:border-archive-black max-md:px-1.5 max-md:py-2.5 md:p-4"
>
    <div
        class="{{ $avatarClass }} shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light"
    >
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="block h-full w-full object-cover object-center"
            loading="lazy"
            decoding="async"
        >
    </div>

    <div class="mt-1.5 w-full min-w-0 max-md:px-0.5 md:mt-3">
        <h3 class="font-display leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:text-[10px] max-md:leading-tight md:text-sm md:leading-snug lg:text-[15px]">
            <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
                <span class="min-w-0">{{ $name }}</span>
                <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90 md:scale-100" />
            </span>
        </h3>

        @if($subtitle)
            <p class="mt-1 leading-snug text-archive-gray line-clamp-2 max-md:hidden md:mt-1.5 md:block md:text-[11px]">
                {{ $subtitle }}
            </p>
        @endif

        @if(isset($badges))
            <div class="mt-1.5 hidden justify-center md:mt-2 md:flex">
                {{ $badges }}
            </div>
        @endif

        @if($meta)
            <p class="mt-0.5 text-archive-gray max-md:text-[9px] max-md:line-clamp-1 md:mt-1.5 md:text-[11px]">
                {{ $meta }}
            </p>
        @endif
    </div>
</a>
