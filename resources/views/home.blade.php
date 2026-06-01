@extends('layouts.app')

@section('title', 'Ads of Iraq — The Archive of Iraqi Advertising')

@section('content')
@include('components.home.hero-slider', ['campaigns' => $heroCampaigns])

@if($featuredCampaigns->count())
<section class="px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-end justify-between md:mb-12">
            <div>
                <p class="section-label mb-2">Curated</p>
                <h2 class="section-title">Featured Campaigns</h2>
            </div>
            <a href="{{ route('campaigns.index') }}" class="text-sm underline underline-offset-4">View all</a>
        </div>
        <x-campaign-grid
            :campaigns="$featuredCampaigns"
            grid-class="grid grid-cols-2 gap-3 sm:gap-6 md:grid-cols-3 lg:grid-cols-3"
        />
    </div>
</section>
@endif

<section class="border-t border-archive-border px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 md:mb-12">
            <p class="section-label mb-2">Archive</p>
            <h2 class="section-title">Latest Campaigns</h2>
        </div>

        @if($latestCampaigns->count())
            <x-campaign-grid
                :campaigns="$latestCampaigns"
                grid-class="grid grid-cols-2 gap-3 sm:gap-6 md:grid-cols-3 lg:grid-cols-4"
            />

            <div class="mt-10 border border-archive-border bg-archive-light px-6 py-10 text-center md:mt-16 md:px-12 md:py-12">
                <p class="font-display text-lg md:text-2xl">Want to explore more work?</p>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-archive-gray">
                    Browse the full Ads of Iraq archive with filters, categories, agencies, brands, and search.
                </p>
                <a href="{{ route('campaigns.index') }}" class="btn-primary mt-6 inline-flex md:mt-8">View All Campaigns</a>
            </div>
        @else
            <p class="text-archive-gray">No campaigns in the archive yet.</p>
        @endif
    </div>
</section>

@if($popularCategories->count())
<section class="border-t border-archive-border px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <p class="section-label mb-2">Browse by</p>
        <h2 class="section-title mb-8 md:mb-12">Popular Categories</h2>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
            @foreach($popularCategories as $category)
                <a href="{{ route('campaigns.index', ['medium' => $category->slug]) }}"
                   class="border border-archive-border px-3 py-5 transition-colors hover:border-archive-black sm:px-6 sm:py-8">
                    <p class="font-display text-sm leading-snug sm:text-lg">{{ $category->name }}</p>
                    <p class="mt-1.5 text-[11px] text-archive-gray sm:mt-2 sm:text-xs">{{ $category->campaigns_count }} campaigns</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($featuredAgencies->count())
<section class="border-t border-archive-border px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 md:mb-12">
            <div>
                <p class="section-label mb-2">Creators</p>
                <h2 class="section-title">Featured Agencies</h2>
            </div>
            <a href="{{ route('agencies.index') }}" class="text-sm underline underline-offset-4">View all</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach($featuredAgencies as $agency)
                <x-home-agency-card :agency="$agency" />
            @endforeach
        </div>
    </div>
</section>
@endif

@if($productionHouses->count())
<section class="border-t border-archive-border px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 md:mb-12">
            <div>
                <p class="section-label mb-2">Production</p>
                <h2 class="section-title">Production Houses</h2>
            </div>
            <a href="{{ route('rankings.top-production-houses') }}" class="text-sm underline underline-offset-4">View rankings</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach($productionHouses as $agency)
                <x-home-agency-card :agency="$agency" :campaign-count="$agency->production_house_campaigns_count" />
            @endforeach
        </div>
    </div>
</section>
@endif

@if($featuredPeople->count())
<section class="border-t border-archive-border px-4 py-12 md:px-8 md:py-20">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 md:mb-12">
            <div>
                <p class="section-label mb-2">Talent</p>
                <h2 class="section-title">People</h2>
            </div>
            <a href="{{ route('people.index') }}" class="text-sm underline underline-offset-4">View all</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach($featuredPeople as $person)
                <x-home-person-card :person="$person" />
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
