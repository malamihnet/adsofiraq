@extends('layouts.app')

@section('title', 'Made By Iraq — Ads of Iraq')
@section('meta_description', 'Iraqi creative talent on the world stage — campaigns, directors, and production craft from Iraq and the diaspora.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="mb-12 border-b border-archive-border pb-12">
        <p class="text-xs uppercase tracking-[0.2em] text-archive-gray">Editorial</p>
        <h1 class="mt-2 font-display text-4xl md:text-5xl">Made By Iraq</h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-archive-gray">
            Showcasing Iraqi talent worldwide — from Baghdad and Erbil to London, Dubai, and beyond.
        </p>
    </div>

    @if($featuredCampaigns->isNotEmpty())
        <section class="mb-16">
            <h2 class="section-label mb-6">Featured campaigns</h2>
            <x-campaign-grid :campaigns="$featuredCampaigns" />
        </section>
    @endif

    @if($featuredCreatives->isNotEmpty())
        <section class="mb-16">
            <h2 class="section-label mb-6">Featured creatives</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($featuredCreatives as $person)
                    <a href="{{ route('person.show', $person) }}" class="border border-archive-border p-4 hover:bg-archive-cream">
                        <p class="font-display">{{ $person->name }}</p>
                        <p class="text-xs text-archive-gray mt-1">{{ $person->position }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="section-label mb-6">All Made By Iraq campaigns</h2>
        <x-campaign-grid :campaigns="$campaigns" />
        <div class="mt-8">{{ $campaigns->links() }}</div>
    </section>
</div>
@endsection
