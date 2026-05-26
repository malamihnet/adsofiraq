@props(['campaigns', 'showActions' => false, 'gridClass' => 'grid gap-8 sm:grid-cols-2 lg:grid-cols-3'])

<div class="{{ $gridClass }}">
    @foreach($campaigns as $campaign)
        <x-campaign-card :campaign="$campaign" :show-actions="$showActions" />
    @endforeach
</div>

@if(method_exists($campaigns, 'links'))
    <div class="mt-12">
        {{ $campaigns->links() }}
    </div>
@endif
