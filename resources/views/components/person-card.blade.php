@props(['person'])

<a href="{{ route('person.show', $person) }}" class="group flex flex-col items-center border border-archive-border bg-white p-4 text-center transition-colors hover:border-archive-black">
    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full border border-archive-border/70 bg-archive-light sm:h-[4.5rem] sm:w-[4.5rem]">
        <img
            src="{{ $person->avatar_url }}"
            alt="{{ $person->name }}"
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            loading="lazy"
        >
    </div>
    <div class="mt-3 w-full min-w-0">
        <h2 class="font-display text-lg leading-snug group-hover:underline inline-flex items-center gap-2">
            {{ $person->name }}
            <x-verified-badge :verified="$person->is_verified" />
        </h2>
        <p class="mt-1 text-xs uppercase tracking-widest text-archive-gray">{{ $person->position }}</p>
    </div>
</a>
