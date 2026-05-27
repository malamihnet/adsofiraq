@props(['campaign', 'showActions' => false])

<article class="group">
    <a href="{{ route('campaigns.show', $campaign) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden border border-archive-border bg-archive-light">
            @if($campaign->thumbnail_url)
                <x-campaign-image src="{{ $campaign->thumbnail_url }}" alt="{{ $campaign->title }}"
                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                     loading="lazy" />
            @else
                <div class="flex h-full items-center justify-center text-archive-gray">
                    <span class="text-xs uppercase tracking-widest">No image</span>
                </div>
            @endif
        </div>
        <div class="mt-4">
            <h3 class="font-display text-lg leading-snug group-hover:underline inline-flex items-center gap-2 flex-wrap">
                {{ $campaign->title }}
                <x-verified-badge :verified="$campaign->is_verified" />
            </h3>
            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-archive-gray">
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
        </div>
    </a>

    @if($showActions)
        <div class="mt-3 flex flex-wrap gap-2">
            <x-bookmark-button :campaign="$campaign" :is-bookmarked="$campaign->is_bookmarked ?? null" size="sm" />
            <x-watch-button :campaign="$campaign" :is-watched="$campaign->is_watched ?? null" size="sm" />
        </div>
    @endif
</article>
