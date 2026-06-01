@props(['campaigns', 'showActions' => false, 'gridClass' => 'grid grid-cols-2 gap-3 sm:gap-6 md:grid-cols-3 lg:grid-cols-3', 'cardVariant' => 'default'])

<div class="{{ $gridClass }}">
    @foreach($campaigns as $campaign)
        <x-campaign-card :campaign="$campaign" :show-actions="$showActions" :variant="$cardVariant" />
    @endforeach
</div>

@if(method_exists($campaigns, 'links') && $campaigns->hasPages())
    <div class="mt-10 border-t border-archive-border/40 pt-8">
        {{ $campaigns->links() }}
    </div>
@endif
