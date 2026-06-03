@props([
    'person',
    'rank',
    'role' => null,
])

<li class="overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition-shadow hover:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
    <div class="flex flex-col gap-5 p-4 sm:flex-row sm:items-center sm:gap-5 sm:p-5">
        <div class="flex min-w-0 flex-1 items-center gap-4 sm:gap-5">
            <span class="w-8 shrink-0 text-center font-display text-2xl tabular-nums text-neutral-300 sm:text-3xl">
                {{ $rank }}
            </span>

            <a
                href="{{ route('person.show', $person) }}"
                class="relative block h-14 w-14 shrink-0 overflow-hidden rounded-full border border-neutral-200/90 bg-white shadow-sm ring-2 ring-white sm:h-16 sm:w-16"
            >
                <img src="{{ $person->photo_url }}" alt="" class="h-full w-full object-cover" loading="lazy" decoding="async">
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('person.show', $person) }}" class="inline-flex max-w-full flex-wrap items-center gap-2">
                    <span class="font-display text-lg leading-tight text-archive-black hover:underline sm:text-xl">
                        {{ $person->name }}
                    </span>
                    <x-verified-badge :verified="$person->is_verified" />
                </a>

                <p class="mt-1 text-sm text-archive-gray">{{ $role ?? $person->position }}</p>

                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Campaigns</dt>
                        <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($person->ranking_campaign_count ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Views</dt>
                        <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($person->ranking_total_views ?? 0) }}</dd>
                    </div>
                    @if(($person->ranking_featured_campaigns ?? 0) > 0)
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Featured</dt>
                            <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($person->ranking_featured_campaigns) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</li>
