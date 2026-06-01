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
        'person' => 'h-10 w-10 md:h-12 md:w-12',
        default => 'h-14 w-14 md:h-14 md:w-14',
    };
@endphp

<a
    href="{{ $href }}"
    class="group flex h-full flex-col items-center rounded-lg border border-archive-border/80 bg-white text-center transition-colors hover:border-archive-black max-md:px-1.5 max-md:py-2.5 md:flex-row md:items-start md:gap-0 md:rounded-none md:border-0 md:bg-transparent md:px-1 md:py-2.5 md:text-left md:hover:bg-archive-light/50"
>
    <div
        class="{{ $avatarClass }} shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light md:border-0"
    >
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="block h-full w-full object-cover object-center"
            loading="lazy"
            decoding="async"
        >
    </div>

    <div class="mt-1.5 w-full min-w-0 max-md:px-0.5 md:mt-0 md:flex-1 md:pl-4">
        <div class="flex flex-col gap-0.5 md:gap-1">
            <h3 class="font-display leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:text-[10px] max-md:leading-tight md:text-sm md:font-medium md:leading-snug">
                <span class="inline-flex items-center gap-1 max-md:justify-center md:justify-start">
                    <span class="min-w-0">{{ $name }}</span>
                    <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90 md:scale-100" />
                </span>
            </h3>

            @if($subtitle)
                <p class="leading-snug text-archive-gray line-clamp-2 max-md:hidden md:text-[11px] md:leading-snug">
                    {{ $subtitle }}
                </p>
            @endif

            @if(isset($badges))
                <div class="hidden max-md:hidden md:block">
                    {{ $badges }}
                </div>
            @endif

            @if($meta)
                <p class="text-archive-gray max-md:text-[9px] max-md:line-clamp-1 md:text-[10px] md:leading-snug">
                    {{ $meta }}
                </p>
            @endif
        </div>
    </div>
</a>
