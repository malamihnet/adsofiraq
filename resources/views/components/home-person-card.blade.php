@props(['person'])

<a
    href="{{ route('person.show', $person) }}"
    class="group flex h-full flex-col border border-archive-border bg-white transition-colors hover:border-archive-black"
>
    <div class="aspect-[4/5] overflow-hidden bg-archive-light">
        <img
            src="{{ $person->photo_url }}"
            alt="{{ $person->name }}"
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
            loading="lazy"
        >
    </div>
    <div class="border-t border-archive-border p-3 sm:p-4">
        <h3 class="font-display text-sm leading-snug group-hover:underline sm:text-base">
            <span class="inline-flex items-start gap-1.5">
                <span class="min-w-0 line-clamp-2">{{ $person->name }}</span>
                <x-verified-badge :verified="$person->is_verified" class="mt-0.5 shrink-0" />
            </span>
        </h3>
        @if($person->position)
            <p class="mt-1 text-[10px] uppercase tracking-wider text-archive-gray line-clamp-2 sm:text-xs">
                {{ $person->position }}
            </p>
        @endif
    </div>
</a>
