@extends('layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs text-archive-gray">
        <a href="{{ route('rankings.index') }}" class="underline decoration-neutral-300 underline-offset-4 hover:text-archive-black">Rankings</a>
    </nav>

    <header class="mb-10">
        <h1 class="font-display text-3xl md:text-4xl">Top Commercials in Iraq</h1>
        <p class="mt-3 max-w-2xl text-sm text-archive-gray">
            TV and film commercials ranked by views, saves, editor picks, and platform scores.
        </p>
    </header>

    <x-campaign-grid :campaigns="$campaigns" />
</div>
@endsection
