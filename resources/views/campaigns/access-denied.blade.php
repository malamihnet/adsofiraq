@extends('layouts.app')

@section('title', 'Access Denied — Ads of Iraq')
@section('meta_description', 'You do not have permission to view this campaign.')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="border-b border-archive-border bg-black text-white">
    <div class="mx-auto max-w-3xl px-4 py-20 text-center md:px-8 md:py-28">
        <p class="section-label mb-6 text-white/60">Access restricted</p>
        <p class="font-display text-7xl leading-none tracking-tight md:text-9xl">403</p>
        <h1 class="mt-8 font-display text-3xl leading-tight md:text-4xl">You don&rsquo;t have permission</h1>
        <p class="mx-auto mt-6 max-w-xl text-sm leading-relaxed text-white/70 md:text-base">
            @if($campaign ?? null)
                You can&rsquo;t view this campaign. It may still be pending review and only visible to the person who submitted it, or it isn&rsquo;t available in the public archive yet.
            @else
                You don&rsquo;t have permission to view this page.
            @endif
        </p>
        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ url('/') }}" class="btn-primary border-white bg-white text-archive-black hover:bg-white/90">
                Back to Home
            </a>
            <a href="{{ route('campaigns.index') }}" class="btn border border-white/30 bg-transparent text-white hover:border-white hover:bg-white hover:text-archive-black">
                Explore Campaigns
            </a>
            @auth
                <a href="{{ route('profile.campaigns') }}" class="btn border border-white/30 bg-transparent text-white hover:border-white hover:bg-white hover:text-archive-black">
                    My Campaigns
                </a>
            @endauth
        </div>
    </div>
</div>

<div class="mx-auto max-w-3xl px-4 py-16 text-center md:px-8">
    <x-logo size="sm" class="mx-auto justify-center opacity-90" />
    <p class="mt-8 text-xs uppercase tracking-widest text-archive-gray">Ads of Iraq</p>
</div>
@endsection
