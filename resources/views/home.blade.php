@extends('layouts.app')

@section('title', 'Ads of Iraq — The Archive of Iraqi Advertising')

@section('content')
@include('components.home.hero-slider', ['campaigns' => $heroCampaigns])

@if($featuredCampaigns->count())
<section class="px-4 py-20 md:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-12 flex items-end justify-between">
            <div>
                <p class="section-label mb-2">Curated</p>
                <h2 class="section-title">Featured Campaigns</h2>
            </div>
            <a href="{{ route('campaigns.index') }}" class="text-sm underline underline-offset-4">View all</a>
        </div>
        <x-campaign-grid :campaigns="$featuredCampaigns" />
    </div>
</section>
@endif

<section class="border-t border-archive-border px-4 py-20 md:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-12">
            <p class="section-label mb-2">Archive</p>
            <h2 class="section-title">Latest Campaigns</h2>
        </div>

        @if($latestCampaigns->count())
            <x-campaign-grid
                :campaigns="$latestCampaigns"
                grid-class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4"
            />

            <div class="mt-16 border border-archive-border bg-archive-light px-8 py-12 text-center md:px-12">
                <p class="font-display text-xl md:text-2xl">Want to explore more work?</p>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-archive-gray">
                    Browse the full Ads of Iraq archive with filters, categories, agencies, brands, and search.
                </p>
                <a href="{{ route('campaigns.index') }}" class="btn-primary mt-8 inline-flex">View All Campaigns</a>
            </div>
        @else
            <p class="text-archive-gray">No campaigns in the archive yet.</p>
        @endif
    </div>
</section>

@if($popularCategories->count())
<section class="border-t border-archive-border px-4 py-20 md:px-8">
    <div class="mx-auto max-w-7xl">
        <p class="section-label mb-2">Browse by</p>
        <h2 class="section-title mb-12">Popular Categories</h2>
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
            @foreach($popularCategories as $category)
                <a href="{{ route('campaigns.index', ['medium' => $category->slug]) }}"
                   class="border border-archive-border px-6 py-8 transition-colors hover:border-archive-black">
                    <p class="font-display text-lg">{{ $category->name }}</p>
                    <p class="mt-2 text-xs text-archive-gray">{{ $category->campaigns_count }} campaigns</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($featuredAgencies->count())
<section class="border-t border-archive-border px-4 py-20 md:px-8">
    <div class="mx-auto max-w-7xl">
        <p class="section-label mb-2">Creators</p>
        <h2 class="section-title mb-12">Featured Agencies</h2>
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            @foreach($featuredAgencies as $agency)
                <a href="{{ route('agencies.show', $agency) }}"
                   class="border border-archive-border px-6 py-6 transition-colors hover:border-archive-black">
                    <p class="font-display text-xl">{{ $agency->name }}</p>
                    <p class="mt-2 text-xs text-archive-gray">{{ $agency->campaigns_count }} campaigns</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
