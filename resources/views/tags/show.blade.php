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
        ['name' => 'Campaigns', 'url' => route('campaigns.index')],
        ['name' => $tag->name, 'url' => null],
    ]" />

    <header class="mb-10">
        <h1 class="font-display text-3xl md:text-4xl">{{ $tag->name }}</h1>
        <p class="mt-3 max-w-2xl text-sm text-archive-gray">
            {{ number_format($tag->campaigns_count) }} campaign{{ $tag->campaigns_count === 1 ? '' : 's' }} tagged on Ads Of Iraq.
        </p>
    </header>

    <x-campaign-grid :campaigns="$campaigns" />

    <div class="mt-10">
        {{ $campaigns->links() }}
    </div>
</div>
@endsection
