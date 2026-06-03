@extends('layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <x-breadcrumbs :items="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => $title, 'url' => null],
    ]" />

    <header class="mb-10 max-w-3xl">
        <h1 class="font-display text-3xl md:text-5xl">{{ $title }}</h1>
        <p class="mt-4 text-sm leading-relaxed text-archive-gray">{{ $intro }}</p>
    </header>

    @if($latestCampaigns->isNotEmpty())
        <section class="mb-14">
            <h2 class="section-label mb-6">Latest Campaigns</h2>
            <x-campaign-grid :campaigns="$latestCampaigns" />
        </section>
    @endif

    @if($topAgencies->isNotEmpty())
        <section class="mb-14">
            <h2 class="section-label mb-6">Top Agencies</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($topAgencies as $agency)
                    <a href="{{ route('agency.show', $agency) }}" class="border border-archive-border p-4 hover:bg-archive-light">
                        <p class="font-display text-lg">{{ $agency->name }}</p>
                        <p class="mt-1 text-xs text-archive-gray">{{ $agency->agency_campaigns_count ?? $agency->ranking_campaign_count ?? '' }} campaigns</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($productionHouses->isNotEmpty())
        <section class="mb-14">
            <h2 class="section-label mb-6">Production Houses</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($productionHouses as $house)
                    <a href="{{ route('agency.show', $house) }}" class="border border-archive-border p-4 hover:bg-archive-light">
                        <p class="font-display text-lg">{{ $house->name }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($brands->isNotEmpty())
        <section class="mb-14">
            <h2 class="section-label mb-6">Brands</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($brands as $brand)
                    <a href="{{ route('brand.show', $brand) }}" class="border border-archive-border p-4 hover:bg-archive-light">
                        <p class="font-display text-lg">{{ $brand->name }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($people->isNotEmpty())
        <section>
            <h2 class="section-label mb-6">Creative People</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($people as $person)
                    <x-person-card :person="$person" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
