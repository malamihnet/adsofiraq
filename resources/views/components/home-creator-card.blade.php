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
    $avatarFrameClass = match ($imageSize) {
        'person' => 'size-10 md:size-16',
        default => 'size-14 md:size-[4.5rem]',
    };
@endphp

<a
    href="{{ $href }}"
    class="group flex h-full flex-col items-center rounded-lg border border-archive-border/80 bg-white px-1.5 py-2.5 text-center transition-colors hover:border-archive-black max-md:min-w-0 md:p-4"
>
    <div
        class="{{ $avatarFrameClass }} relative shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light"
        aria-hidden="true"
    >
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="absolute inset-0 h-full w-full max-w-none object-cover object-center"
            loading="lazy"
            decoding="async"
        >
    </div>

    <div class="mt-1.5 w-full min-w-0 max-md:px-0.5 md:mt-3">
        <h3 class="font-display text-[10px] leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:leading-tight md:text-sm lg:text-[15px]">
            <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
                <span class="min-w-0">{{ $name }}</span>
                <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90" />
            </span>
        </h3>

        @if($subtitle)
            <p class="mt-0.5 text-[9px] leading-snug text-archive-gray line-clamp-2 max-md:hidden md:mt-1.5 md:block md:text-[11px]">
                {{ $subtitle }}
            </p>
        @endif

        @if(isset($badges))
            <div class="mt-1 flex justify-center max-md:hidden md:mt-2 md:flex">
                {{ $badges }}
            </div>
        @endif

        @if($meta)
            <p class="mt-0.5 text-[9px] text-archive-gray max-md:line-clamp-1 md:mt-1.5 md:text-[11px]">{{ $meta }}</p>
        @endif
    </div>
</a>
