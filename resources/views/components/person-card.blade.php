@props(['person'])

<a href="{{ route('people.show', $person) }}" class="group block border border-archive-border bg-white transition-colors hover:border-archive-black">
    <div class="aspect-[4/5] overflow-hidden bg-archive-light">
        <img
            src="{{ $person->photo_url }}"
            alt="{{ $person->name }}"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
            loading="lazy"
        >
    </div>
    <div class="border-t border-archive-border p-4">
        <h2 class="font-display text-lg leading-snug group-hover:underline inline-flex items-center gap-2">
            {{ $person->name }}
            <x-verified-badge :verified="$person->is_verified" />
        </h2>
        <p class="mt-1 text-xs uppercase tracking-widest text-archive-gray">{{ $person->position }}</p>
    </div>
</a>
