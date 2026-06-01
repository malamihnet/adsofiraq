@props([
    'href',
    'name',
    'verified' => false,
    'subtitle' => null,
    'meta' => null,
])

<a
    href="{{ $href }}"
    class="group flex h-full w-full min-w-0 flex-col items-center justify-center rounded-2xl border border-archive-border/80 bg-white px-2 py-3 text-center transition-colors hover:border-archive-black md:px-3 md:py-4"
>
    <h3 class="w-full min-w-0 font-display text-sm font-medium leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:text-[11px] max-md:leading-tight md:text-base">
        <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
            <span class="min-w-0">{{ $name }}</span>
            <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90 md:scale-100" />
        </span>
    </h3>

    @if(isset($badges))
        <div class="w-full max-w-full">
            {{ $badges }}
        </div>
    @endif

    @if($subtitle)
        <p class="mt-1 w-full max-w-full text-center text-[10px] font-normal leading-snug text-archive-gray line-clamp-2 max-md:text-[9px]">
            {{ $subtitle }}
        </p>
    @endif

    @if($meta)
        <p class="mt-1 w-full text-xs font-normal text-archive-gray/90 max-md:text-[10px]">
            {{ $meta }}
        </p>
    @endif
</a>
