@extends('layouts.app')

@section('title', 'Bookmarks — Ads of Iraq')
@section('meta_description', 'Your saved campaigns from the Ads of Iraq archive.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <header class="mb-12 max-w-2xl">
        <h1 class="section-title">Bookmarks</h1>
        <p class="mt-4 text-archive-gray leading-relaxed">
            Campaigns you've saved from the Ads of Iraq archive.
        </p>
    </header>

    @if($campaigns->count())
        <x-campaign-grid :campaigns="$campaigns" :show-actions="true" />
    @else
        <div class="border border-archive-border bg-archive-light px-8 py-16 text-center">
            <svg class="mx-auto h-10 w-10 text-archive-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <p class="mt-6 font-display text-xl text-archive-black">No bookmarks yet</p>
            <p class="mx-auto mt-3 max-w-md text-sm text-archive-gray">
                Browse the archive and bookmark campaigns to build your personal collection.
            </p>
            <a href="{{ route('campaigns.index') }}" class="btn-primary mt-8 inline-flex text-xs">Browse campaigns</a>
        </div>
    @endif
</div>
@endsection
