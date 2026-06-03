@extends('layouts.app')

@section('title', "Editor's Pick — Ads of Iraq")

@section('meta_description', "Curated campaigns selected by the Ads of Iraq editorial team.")

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="mb-12">
        <p class="section-label mb-2">Curated</p>
        <h1 class="section-title">Editor's Pick</h1>
        <p class="mt-4 max-w-2xl text-sm leading-relaxed text-archive-gray">
            Campaigns highlighted by our editors for exceptional creative work in the Iraqi advertising archive.
            Separate from the homepage hero slider, which features rotating spotlight campaigns.
        </p>
    </div>

    @if($campaigns->count())
        <x-campaign-grid
            :campaigns="$campaigns"
            grid-class="grid grid-cols-2 gap-3 sm:gap-6 md:grid-cols-3 lg:grid-cols-4"
        />
        <div class="mt-10">{{ $campaigns->links() }}</div>
    @else
        <p class="text-archive-gray">No Editor's Pick campaigns yet. Check back soon.</p>
    @endif
</div>
@endsection
