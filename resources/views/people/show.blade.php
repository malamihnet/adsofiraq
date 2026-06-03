@extends('layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])
@section('og_image', $person->photo_url)

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <x-breadcrumbs :items="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'People', 'url' => route('people.index')],
        ['name' => $person->name, 'url' => null],
    ]" />
    <div class="grid gap-12 border-b border-archive-border pb-12 lg:grid-cols-[320px_1fr]">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-40 w-40 shrink-0 overflow-hidden rounded-full border border-archive-border bg-archive-light sm:h-48 sm:w-48">
                <img
                    src="{{ $person->avatar_url }}"
                    alt="{{ $person->name }}"
                    class="h-full w-full object-cover"
                >
            </div>
        </div>
        <div>
            <h1 class="font-display text-3xl md:text-4xl inline-flex items-center gap-2 flex-wrap">
                {{ $person->name }}
                <x-verified-badge :verified="$person->is_verified" />
            </h1>
            <p class="mt-2 text-sm uppercase tracking-widest text-archive-gray">{{ $person->display_position }}</p>

            @if($creditedCampaignsCount > 0)
                <p class="mt-3 text-sm text-archive-gray">{{ $creditedCampaignsCount }} credited campaign{{ $creditedCampaignsCount === 1 ? '' : 's' }} on Ads Of Iraq</p>
            @endif

            @if($person->production_house)
                <p class="mt-4 text-sm">Production house: <span class="font-medium">{{ $person->production_house }}</span></p>
            @endif

            @if($person->bio)
                <p class="mt-8 max-w-2xl leading-relaxed">{{ $person->bio }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-4 text-sm">
                @if($person->profile_link)
                    <a href="{{ $person->profile_link }}" target="_blank" rel="noopener noreferrer" class="underline">Official profile</a>
                @endif
                @if($person->website_url)
                    <a href="{{ $person->website_url }}" target="_blank" rel="noopener" class="underline">Website</a>
                @endif
                @if($person->instagram_url)
                    <a href="{{ $person->instagram_url }}" target="_blank" rel="noopener" class="underline">Instagram</a>
                @endif
                @if($person->linkedin_url)
                    <a href="{{ $person->linkedin_url }}" target="_blank" rel="noopener" class="underline">LinkedIn</a>
                @endif
            </div>

            @if($person->featured_works)
                <div class="mt-10">
                    <h2 class="section-label mb-4">Featured work</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach($person->featured_works as $work)
                            <li class="border-l-2 border-archive-black pl-4">{{ $work }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if($creditedCampaigns->isNotEmpty())
        <section class="mt-16 border-t border-archive-border pt-12">
            <h2 class="section-label mb-8">Campaigns credited on</h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($creditedCampaigns as $credited)
                    <div>
                        <x-campaign-card :campaign="$credited" />
                        @if($credited->pivot?->role)
                            <p class="mt-2 text-xs uppercase tracking-widest text-archive-gray">
                                Role: <span class="text-archive-black">{{ $credited->pivot->role }}</span>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($relatedPeople->isNotEmpty())
        <section class="mt-16 border-t border-archive-border pt-12">
            <h2 class="section-label mb-8">Related People</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($relatedPeople as $related)
                    <x-person-card :person="$related" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
