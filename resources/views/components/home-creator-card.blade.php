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
        'person' => 'h-12 w-12 min-h-12 min-w-12 max-h-12 max-w-12 md:h-16 md:w-16 md:min-h-16 md:min-w-16 md:max-h-16 md:max-w-16',
        default => 'h-14 w-14 min-h-14 min-w-14 max-h-14 max-w-14 md:h-20 md:w-20 md:min-h-20 md:min-w-20 md:max-h-20 md:max-w-20',
    };
@endphp

<a
    href="{{ $href }}"
    class="group flex h-full min-h-[7.5rem] flex-col items-center rounded-lg border border-archive-border/80 bg-white text-center transition-colors hover:border-archive-black max-md:min-h-[6.5rem] max-md:px-2 max-md:py-3 md:min-h-[11rem] md:border-archive-border/80 md:p-4 md:hover:border-archive-black"
>
    <div class="{{ $avatarClass }} mx-auto shrink-0 overflow-hidden rounded-full bg-archive-light">
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="block h-full w-full rounded-full object-cover object-center"
            loading="lazy"
            decoding="async"
            width="80"
            height="80"
        >
    </div>

    <div class="mt-2 w-full min-w-0 flex-1 max-md:px-0.5 md:mt-3">
        <h3 class="font-display leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:text-[10px] max-md:leading-tight md:text-sm md:leading-snug lg:text-[15px]">
            <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
                <span class="min-w-0">{{ $name }}</span>
                <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90 md:scale-100" />
            </span>
        </h3>

        @if($subtitle)
            <p class="mt-1 leading-snug text-archive-gray line-clamp-2 max-md:text-[10px] max-md:leading-snug md:mt-1.5 md:text-[11px]">
                {{ $subtitle }}
            </p>
        @endif

        @if(isset($badges))
            <div class="mt-1.5 flex justify-center md:mt-2">
                {{ $badges }}
            </div>
        @endif

        @if($meta)
            <p class="mt-1 text-archive-gray max-md:text-[10px] max-md:line-clamp-1 md:mt-1.5 md:text-[11px]">
                {{ $meta }}
            </p>
        @endif
    </div>
</a>
