@extends('layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])
@if($brand->hasLogo())
    @section('og_image', $brand->logo_url)
@endif

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="agency-profile-page bg-white">
    <div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
        <x-breadcrumbs :items="[
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Brands', 'url' => route('brands.index')],
            ['name' => $brand->name, 'url' => null],
        ]" />

        <x-brand-profile-header :brand="$brand" :stats="$stats" />

        <section class="mt-14 sm:mt-16">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-archive-border/40 pb-4">
                <h2 class="text-[11px] font-medium uppercase tracking-[0.22em] text-archive-gray">Campaigns</h2>
                @if($campaigns->total() > 0)
                    <p class="text-xs text-archive-gray">{{ number_format($campaigns->total()) }} total</p>
                @endif
            </div>

            @if($campaigns->isNotEmpty())
                <x-campaign-grid
                    :campaigns="$campaigns"
                    card-variant="profile"
                    grid-class="grid gap-7 sm:grid-cols-2 lg:gap-8 xl:grid-cols-3"
                />
            @else
                <div class="rounded-2xl border border-dashed border-archive-border/70 bg-archive-cream/30 px-6 py-20 text-center">
                    <p class="text-sm text-archive-gray">No published campaigns yet.</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
