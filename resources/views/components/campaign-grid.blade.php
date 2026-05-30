@props(['campaigns', 'showActions' => false, 'gridClass' => 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3'])

<div class="{{ $gridClass }}">
    @foreach($campaigns as $campaign)
        <x-campaign-card :campaign="$campaign" :show-actions="$showActions" />
    @endforeach
</div>

@if(method_exists($campaigns, 'links') && $campaigns->hasPages())
    <div class="mt-10 border-t border-archive-border/40 pt-8">
        {{ $campaigns->links() }}
    </div>
@endif
