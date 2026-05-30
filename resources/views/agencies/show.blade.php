@extends('layouts.app')

@section('title', $agency->seo_title)
@section('meta_description', $agency->seo_description)
@if($agency->logo_url)
    @section('og_image', $agency->logo_url)
@endif

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 md:px-8 md:py-14">
    <p class="mb-8 text-xs text-archive-gray">
        <a href="{{ $parentUrl }}" class="underline decoration-archive-border underline-offset-4 hover:text-archive-black">{{ $parentLabel }}</a>
    </p>

    <x-agency-profile-header :agency="$agency" :stats="$stats" />

    <section>
        @if($campaigns->isNotEmpty())
            <h2 class="mb-6 text-[10px] font-medium uppercase tracking-[0.2em] text-archive-gray">Campaigns</h2>
            <x-campaign-grid
                :campaigns="$campaigns"
                grid-class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            />
        @else
            <p class="border border-dashed border-archive-border/80 py-16 text-center text-sm text-archive-gray">
                No published campaigns yet.
            </p>
        @endif
    </section>
</div>
@endsection
