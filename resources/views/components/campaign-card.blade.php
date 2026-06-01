@props(['campaign', 'showActions' => false, 'variant' => 'default'])

@php
    $isProfile = $variant === 'profile';

    $metaParts = collect()
        ->merge($campaign->brands->map(fn ($b) => $b->name))
        ->merge($campaign->agencies->map(fn ($a) => $a->name))
        ->merge($campaign->mediumTypes->map(fn ($m) => $m->name))
        ->filter()
        ->take(3);
@endphp

<article @class([
    'group',
    'rounded-2xl border border-neutral-200/80 bg-white p-2.5 shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition-all duration-300 hover:border-neutral-300/90 hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)] sm:p-3' => $isProfile,
])>
    <a href="{{ route('campaigns.show', $campaign) }}" class="block">
        <div @class([
            'overflow-hidden bg-archive-light',
            'aspect-[4/3] rounded-xl' => $isProfile,
            'aspect-[4/3] border border-archive-border/70 transition-colors duration-300 group-hover:border-archive-black/30' => ! $isProfile,
        ])>
            <x-campaign-image
                src="{{ $campaign->thumbnail_url }}"
                alt="{{ $campaign->title }}"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.02]"
                loading="lazy"
            />
        </div>

        <div @class([
            'px-1',
            'pt-4 pb-1' => $isProfile,
            'mt-2 sm:mt-3.5' => ! $isProfile,
        ])>
            <h3 @class([
                'font-display leading-snug text-archive-black',
                'text-[15px] font-medium line-clamp-2 group-hover:underline' => $isProfile,
                'text-sm sm:text-base md:text-[17px] inline-flex flex-wrap items-center gap-1.5 sm:gap-2' => ! $isProfile,
            ])>
                @if($isProfile)
                    <span class="flex items-start gap-2">
                        <span class="min-w-0 flex-1">{{ $campaign->title }}</span>
                        <x-verified-badge :verified="$campaign->is_verified" />
                    </span>
                @else
                    <span class="group-hover:underline">{{ $campaign->title }}</span>
                    <x-verified-badge :verified="$campaign->is_verified" />
                @endif
            </h3>

            @if($isProfile)
                @if($metaParts->isNotEmpty())
                    <p class="mt-2.5 text-xs leading-relaxed text-archive-gray line-clamp-2">
                        {{ $metaParts->implode(' · ') }}
                    </p>
                @endif
            @else
                <div class="mt-1 hidden flex-wrap gap-x-2 gap-y-0.5 text-[10px] leading-relaxed text-archive-gray sm:mt-1.5 sm:flex sm:gap-x-2.5 sm:text-[11px]">
                    @foreach($campaign->brands as $brand)
                        <span class="inline-flex items-center gap-1">
                            {{ $brand->name }}
                            <x-verified-badge :verified="$brand->is_verified" />
                        </span>
                    @endforeach
                    @foreach($campaign->agencies as $agency)
                        <span class="inline-flex items-center gap-1">
                            @if($campaign->brands->isNotEmpty()) &middot; @endif
                            {{ $agency->name }}
                            <x-verified-badge :verified="$agency->is_verified" />
                        </span>
                    @endforeach
                    @foreach($campaign->mediumTypes as $medium)
                        <span>
                            @if($campaign->brands->isNotEmpty() || $campaign->agencies->isNotEmpty()) &middot; @endif
                            {{ $medium->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </a>

    @if($showActions)
        <div class="mt-3 flex flex-wrap gap-2 px-1">
            <x-bookmark-button :campaign="$campaign" :is-bookmarked="$campaign->is_bookmarked ?? null" size="sm" />
            <x-watch-button :campaign="$campaign" :is-watched="$campaign->is_watched ?? null" size="sm" />
        </div>
    @endif
</article>
