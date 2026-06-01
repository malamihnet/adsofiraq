@props([
    'href',
    'name',
    'verified' => false,
    'subtitle' => null,
    'meta' => null,
])

<a
    href="{{ $href }}"
    class="group flex h-full w-full min-w-0 flex-col items-center justify-center rounded-2xl border border-archive-border/80 bg-white px-2 py-3 text-center transition-colors hover:border-archive-black max-md:min-h-[4.75rem] md:min-h-[6.5rem] md:px-3 md:py-4"
>
    <h3 class="font-display w-full min-w-0 leading-snug text-archive-black line-clamp-2 group-hover:underline max-md:text-[10px] max-md:leading-tight md:text-sm md:leading-snug">
        <span class="inline-flex items-center justify-center gap-0.5 md:gap-1">
            <span class="min-w-0">{{ $name }}</span>
            <x-verified-badge :verified="$verified" class="shrink-0 max-md:scale-90 md:scale-100" />
        </span>
    </h3>

    @if($subtitle)
        <p class="mt-1 w-full min-w-0 leading-snug text-archive-gray line-clamp-2 max-md:text-[10px] md:text-[11px]">
            {{ $subtitle }}
        </p>
    @endif

    @if(isset($badges))
        <div class="mt-1.5 flex w-full justify-center md:mt-2">
            {{ $badges }}
        </div>
    @endif

    @if($meta)
        <p class="mt-1 w-full text-archive-gray max-md:text-[10px] md:text-[11px]">
            {{ $meta }}
        </p>
    @endif
</a>
