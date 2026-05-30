@extends('layouts.app')

@section('title', $agency->seo_title)
@section('meta_description', $agency->seo_description)
@if($agency->hasLogo())
    @section('og_image', $agency->logo_url)
@endif

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="agency-profile-page bg-white">
    <div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
        @auth
            @if(auth()->user()->isAdmin())
                <div class="mb-6 flex justify-end">
                    <a
                        href="{{ route('admin.agencies.show', $agency->id) }}"
                        class="inline-flex items-center rounded-full border border-neutral-200/90 bg-white px-4 py-2 text-xs font-medium text-neutral-600 shadow-sm transition-colors hover:border-neutral-300 hover:text-archive-black"
                    >
                        Edit profile
                    </a>
                </div>
            @endif
        @endauth

        <nav aria-label="Breadcrumb" class="mb-10 sm:mb-12">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-archive-gray">
                <li>
                    <a href="{{ route('home') }}" class="transition-colors hover:text-archive-black">Home</a>
                </li>
                <li aria-hidden="true" class="text-archive-border">/</li>
                <li>
                    <a href="{{ $parentUrl }}" class="transition-colors hover:text-archive-black">{{ $parentLabel }}</a>
                </li>
                <li aria-hidden="true" class="text-archive-border">/</li>
                <li class="font-medium text-archive-black" aria-current="page">{{ $agency->name }}</li>
            </ol>
        </nav>

        <x-agency-profile-header :agency="$agency" :stats="$stats" />

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
