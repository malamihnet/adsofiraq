@extends('layouts.app')

@section('title', 'Watching — Ads of Iraq')
@section('meta_description', 'Campaigns you are following from the Ads of Iraq archive.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <header class="mb-12 max-w-2xl">
        <h1 class="section-title">Watching</h1>
        <p class="mt-4 text-archive-gray leading-relaxed">
            Campaigns you're following from the Ads of Iraq archive.
        </p>
    </header>

    @if($campaigns->count())
        <x-campaign-grid :campaigns="$campaigns" :show-actions="true" />
    @else
        <div class="border border-archive-border bg-archive-light px-8 py-16 text-center">
            <svg class="mx-auto h-10 w-10 text-archive-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="mt-6 font-display text-xl text-archive-black">Nothing in your watch list yet</p>
            <p class="mx-auto mt-3 max-w-md text-sm text-archive-gray">
                Watch campaigns from the archive to keep track of work you want to revisit.
            </p>
            <a href="{{ route('campaigns.index') }}" class="btn-primary mt-8 inline-flex text-xs">Browse campaigns</a>
        </div>
    @endif
</div>
@endsection
