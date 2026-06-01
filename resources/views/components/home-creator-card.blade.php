@props([
    'href',
    'imageUrl',
    'name',
    'verified' => false,
    'subtitle' => null,
    'meta' => null,
])

<a
    href="{{ $href }}"
    class="group flex h-full flex-col items-center rounded-lg border border-archive-border/80 bg-white p-4 text-center transition-colors hover:border-archive-black"
>
    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light sm:h-[4.5rem] sm:w-[4.5rem]">
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            loading="lazy"
        >
    </div>

    <div class="mt-3 w-full min-w-0">
        <h3 class="font-display text-sm leading-snug text-archive-black line-clamp-2 group-hover:underline sm:text-[15px]">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="min-w-0">{{ $name }}</span>
                <x-verified-badge :verified="$verified" class="shrink-0" />
            </span>
        </h3>

        @if($subtitle)
            <p class="mt-1.5 text-[10px] leading-snug text-archive-gray line-clamp-2 sm:text-[11px]">
                {{ $subtitle }}
            </p>
        @endif

        @if(isset($badges))
            <div class="mt-2 flex justify-center">
                {{ $badges }}
            </div>
        @endif

        @if($meta)
            <p class="mt-1.5 text-[10px] text-archive-gray sm:text-[11px]">{{ $meta }}</p>
        @endif
    </div>
</a>
