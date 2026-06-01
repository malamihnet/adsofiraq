@props(['agency', 'campaignCount' => null])

@php
    $count = $campaignCount ?? ($agency->campaigns_count ?? $agency->production_house_campaigns_count ?? null);
@endphp

<a
    href="{{ route('agency.show', $agency) }}"
    class="group flex h-full flex-col border border-archive-border bg-white p-3 transition-colors hover:border-archive-black sm:p-4"
>
    <div class="aspect-square w-full overflow-hidden border border-archive-border/70 bg-archive-light">
        <img
            src="{{ $agency->logo_url }}"
            alt=""
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
            loading="lazy"
        >
    </div>
    <div class="mt-3 min-w-0 flex-1">
        <h3 class="font-display text-sm leading-snug text-archive-black line-clamp-2 group-hover:underline sm:text-base">
            <span class="inline-flex items-start gap-1.5">
                <span class="min-w-0">{{ $agency->name }}</span>
                <x-verified-badge :verified="$agency->is_verified" class="mt-0.5 shrink-0" />
            </span>
        </h3>
        @if($agency->roleLabels() !== [])
            <div class="mt-2 flex flex-wrap gap-1">
                <x-agency-role-badges :roles="$agency->roleLabels()" class="gap-1" />
            </div>
        @endif
        @if($count !== null)
            <p class="mt-2 text-[11px] text-archive-gray">{{ $count }} campaigns</p>
        @endif
    </div>
</a>
