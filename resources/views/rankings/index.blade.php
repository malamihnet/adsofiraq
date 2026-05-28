@extends('layouts.app')

@section('title', 'Creative Rankings — Ads of Iraq')
@section('meta_description', 'Data-driven rankings of Iraq’s top agencies, production houses, and campaigns — based on views, saves, and editorial quality.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <h1 class="section-title mb-4">Rankings</h1>
    <p class="max-w-2xl text-archive-gray mb-12">Scores combine real engagement (views, saves), editorial recognition, and recency — not manual hype.</p>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('rankings.top-agencies') }}" class="border border-archive-border p-6 hover:bg-archive-cream transition">
            <h2 class="font-display text-xl">Top Agencies</h2>
            <p class="mt-2 text-sm text-archive-gray">All-time agency leaderboard</p>
        </a>
        <a href="{{ route('rankings.top-production-houses') }}" class="border border-archive-border p-6 hover:bg-archive-cream transition">
            <h2 class="font-display text-xl">Top Production Houses</h2>
            <p class="mt-2 text-sm text-archive-gray">Post & film production leaders</p>
        </a>
        <a href="{{ route('rankings.most-viewed') }}" class="border border-archive-border p-6 hover:bg-archive-cream transition">
            <h2 class="font-display text-xl">Most Viewed</h2>
            <p class="mt-2 text-sm text-archive-gray">Campaigns the industry is watching</p>
        </a>
        <a href="{{ route('rankings.trending') }}" class="border border-archive-border p-6 hover:bg-archive-cream transition">
            <h2 class="font-display text-xl">Trending</h2>
            <p class="mt-2 text-sm text-archive-gray">Momentum-weighted campaigns</p>
        </a>
        <a href="{{ route('rankings.most-appreciated') }}" class="border border-archive-border p-6 hover:bg-archive-cream transition">
            <h2 class="font-display text-xl">Most Appreciated</h2>
            <p class="mt-2 text-sm text-archive-gray">Highest saves & bookmarks</p>
        </a>
    </div>
</div>
@endsection
