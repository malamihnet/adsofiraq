@props([
    'agency',
    'rank',
    'campaignCount' => 0,
    'totalViews' => 0,
    'featuredCount' => null,
    'preview' => null,
])

<li class="overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition-shadow hover:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
    <div class="flex flex-col gap-5 p-4 sm:flex-row sm:items-center sm:gap-5 sm:p-5">
        <div class="flex min-w-0 flex-1 items-center gap-4 sm:gap-5">
            <span class="w-8 shrink-0 text-center font-display text-2xl tabular-nums text-neutral-300 sm:text-3xl">
                {{ $rank }}
            </span>

            <a
                href="{{ route('agency.show', $agency) }}"
                class="relative block h-14 w-14 shrink-0 overflow-hidden rounded-full border border-neutral-200/90 bg-white shadow-sm ring-2 ring-white sm:h-16 sm:w-16"
                aria-label="{{ $agency->name }} profile"
            >
                @if($agency->logo_url)
                    <img
                        src="{{ $agency->logo_url }}"
                        alt=""
                        class="h-full w-full object-contain object-center p-0"
                        loading="lazy"
                        decoding="async"
                    >
                @else
                    <span class="flex h-full w-full items-center justify-center bg-neutral-50 font-display text-lg text-neutral-400 sm:text-xl">
                        {{ mb_substr($agency->name, 0, 1) }}
                    </span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('agency.show', $agency) }}" class="inline-flex max-w-full flex-wrap items-center gap-2">
                    <span class="font-display text-lg leading-tight text-archive-black hover:underline sm:text-xl">
                        {{ $agency->name }}
                    </span>
                    <x-verified-badge :verified="$agency->is_verified" />
                </a>

                @if($agency->roleLabels() !== [])
                    <div class="mt-2">
                        <x-agency-role-badges :roles="$agency->roleLabels()" />
                    </div>
                @endif

                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Campaigns</dt>
                        <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($campaignCount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Views</dt>
                        <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($totalViews) }}</dd>
                    </div>
                    @if($featuredCount !== null && $featuredCount > 0)
                        <div>
                            <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Featured</dt>
                            <dd class="mt-0.5 font-display text-lg tabular-nums text-archive-black">{{ number_format($featuredCount) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        @if($preview)
            <a
                href="{{ route('campaigns.show', $preview) }}"
                class="group block w-full shrink-0 sm:w-36 md:w-40"
            >
                <div class="aspect-[4/3] overflow-hidden rounded-xl border border-neutral-200/80 bg-neutral-50">
                    @if($preview->thumbnail_url)
                        <x-campaign-image
                            src="{{ $preview->thumbnail_url }}"
                            alt="{{ $preview->title }}"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                            loading="lazy"
                        />
                    @endif
                </div>
                <p class="mt-2 line-clamp-2 text-[11px] leading-snug text-archive-gray group-hover:underline">{{ $preview->title }}</p>
            </a>
        @endif
    </div>
</li>
