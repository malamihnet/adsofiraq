@extends('layouts.app')

@section('title', $campaign->title . ' — Ads of Iraq')
@section('meta_description', $campaign->seo_description)
@if($campaign->thumbnail_url)
    @section('og_image', $campaign->thumbnail_url)
@endif
@section('og_type', 'article')

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    @if(auth()->check() && auth()->user()->can('update', $campaign))
        <div class="mb-8 flex flex-wrap items-center gap-4 border border-archive-border p-4">
            @if($campaign->status !== 'approved')
                <x-status-badge :status="$campaign->status" />
            @endif
            <a href="{{ route('campaigns.edit', $campaign) }}" class="text-sm underline">Edit Campaign</a>

            @if($campaign->status === 'approved' && $campaign->pendingRevision)
                <span class="text-sm text-archive-gray">Your changes are pending review. Public visitors still see the approved version.</span>
            @endif
        </div>
    @endif

    <div class="grid gap-12 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h1 class="font-display text-3xl leading-tight md:text-5xl inline-flex items-center gap-3 flex-wrap">
                {{ $campaign->title }}
                <x-verified-badge :verified="$campaign->is_verified" />
                <x-editorial-label :label="$campaign->editorial_label_display" />
            </h1>

            @if($campaign->ai_summary)
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-archive-gray">{{ $campaign->ai_summary }}</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-x-3 gap-y-2 text-sm text-archive-gray">
                @foreach($campaign->brands as $brand)
                    <a href="{{ route('brand.show', $brand) }}" class="inline-flex items-center gap-1.5 hover:text-archive-black">
                        {{ $brand->name }}
                        <x-verified-badge :verified="$brand->is_verified" />
                    </a>
                @endforeach
                @foreach($campaign->agencies as $agency)
                    @if($campaign->brands->isNotEmpty() || ! $loop->first)<span>&middot;</span>@endif
                    <a href="{{ route('agency.show', $agency) }}" class="inline-flex items-center gap-1.5 hover:text-archive-black">
                        {{ $agency->name }}
                        <x-verified-badge :verified="$agency->is_verified" />
                    </a>
                @endforeach
                @foreach($campaign->productionHouses as $productionHouse)
                    @if($campaign->brands->isNotEmpty() || $campaign->agencies->isNotEmpty() || ! $loop->first)<span>&middot;</span>@endif
                    <a href="{{ route('agency.show', $productionHouse) }}" class="inline-flex items-center gap-1.5 hover:text-archive-black">
                        {{ $productionHouse->name }}
                        <x-verified-badge :verified="$productionHouse->is_verified" />
                        <x-production-house-badge />
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                <x-video-embed :campaign="$campaign" />
            </div>

            @php $galleryStills = $campaign->galleryStills(); @endphp
            @if($galleryStills->isNotEmpty())
                <div class="mt-8">
                    <x-campaign-gallery :stills="$galleryStills" :title="$campaign->title" />
                </div>
            @endif

            <div class="mt-12 max-w-none">
                <h2 class="section-label mb-4">Description</h2>
                <div class="leading-relaxed whitespace-pre-line">{!! nl2br(e($campaign->description)) !!}</div>

                @if($campaign->credits)
                    <h2 class="section-label mb-4 mt-12">Credits</h2>
                    <div class="leading-relaxed whitespace-pre-line text-sm">{!! nl2br(e($campaign->credits)) !!}</div>
                @endif
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <x-bookmark-button :campaign="$campaign" :is-bookmarked="$isBookmarked" />
                <x-watch-button :campaign="$campaign" :is-watched="$isWatched" />
                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($campaign->title) }}"
                   target="_blank" rel="noopener" class="btn-outline text-xs">Share on X</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                   target="_blank" rel="noopener" class="btn-outline text-xs">Share on Facebook</a>
            </div>
        </div>

        <aside class="space-y-8">
            <div class="border border-archive-border p-6">
                <h3 class="section-label mb-6">Details</h3>
                <dl class="space-y-4 text-sm">
                    @if($campaign->brands->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Brand{{ $campaign->brands->count() > 1 ? 's' : '' }}</dt>
                            <dd class="mt-1 space-y-1">
                                @foreach($campaign->brands as $brand)
                                    <div>
                                        <a href="{{ route('brand.show', $brand) }}" class="inline-flex items-center gap-1 underline">
                                            {{ $brand->name }}
                                            <x-verified-badge :verified="$brand->is_verified" />
                                        </a>
                                    </div>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if($campaign->agencies->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Agenc{{ $campaign->agencies->count() > 1 ? 'ies' : 'y' }}</dt>
                            <dd class="mt-1 space-y-1">
                                @foreach($campaign->agencies as $agency)
                                    <div>
                                        <a href="{{ route('agency.show', $agency) }}" class="inline-flex items-center gap-1 underline">
                                            {{ $agency->name }}
                                            <x-verified-badge :verified="$agency->is_verified" />
                                        </a>
                                    </div>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if($campaign->productionHouses->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Production {{ $campaign->productionHouses->count() > 1 ? 'Houses' : 'House' }}</dt>
                            <dd class="mt-1 space-y-1">
                                @foreach($campaign->productionHouses as $productionHouse)
                                    <div>
                                        <a href="{{ route('agency.show', $productionHouse) }}" class="inline-flex items-center gap-1 underline">
                                            {{ $productionHouse->name }}
                                            <x-verified-badge :verified="$productionHouse->is_verified" />
                                            <x-production-house-badge />
                                        </a>
                                    </div>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if($campaign->industries->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Industr{{ $campaign->industries->count() > 1 ? 'ies' : 'y' }}</dt>
                            <dd class="mt-1">{{ $campaign->industries->pluck('name')->join(', ') }}</dd>
                        </div>
                    @endif
                    @if($campaign->mediumTypes->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Medium</dt>
                            <dd class="mt-1">{{ $campaign->mediumTypes->pluck('name')->join(', ') }}</dd>
                        </div>
                    @endif
                    @if($campaign->countries->isNotEmpty())
                        <div>
                            <dt class="text-archive-gray">Countr{{ $campaign->countries->count() > 1 ? 'ies' : 'y' }}</dt>
                            <dd class="mt-1">{{ $campaign->countries->pluck('name')->join(', ') }}</dd>
                        </div>
                    @endif
                    @if($campaign->published_at)
                        <div><dt class="text-archive-gray">Year</dt><dd class="mt-1">{{ $campaign->published_at->format('Y') }}</dd></div>
                    @endif
                </dl>
            </div>
        </aside>
    </div>

    <x-campaign-related-sections :groups="$relatedGroups" />
</div>
@endsection
