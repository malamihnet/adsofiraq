@extends('layouts.app')

@section('title', $person->seo_title)
@section('meta_description', $person->seo_description)
@section('og_image', $person->photo_url)

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="grid gap-12 border-b border-archive-border pb-12 lg:grid-cols-[320px_1fr]">
        <div class="flex flex-col items-center lg:items-start">
            <div class="h-40 w-40 shrink-0 overflow-hidden rounded-full border border-archive-border bg-archive-light sm:h-48 sm:w-48">
                <img
                    src="{{ $person->avatar_url }}"
                    alt="{{ $person->name }}"
                    class="h-full w-full object-cover"
                >
            </div>
        </div>
        <div>
            <h1 class="font-display text-3xl md:text-4xl inline-flex items-center gap-2 flex-wrap">
                {{ $person->name }}
                <x-verified-badge :verified="$person->is_verified" />
            </h1>
            <p class="mt-2 text-sm uppercase tracking-widest text-archive-gray">{{ $person->position }}</p>

            @if($person->production_house)
                <p class="mt-4 text-sm">Production house: <span class="font-medium">{{ $person->production_house }}</span></p>
            @endif

            @if($person->bio)
                <p class="mt-8 max-w-2xl leading-relaxed">{{ $person->bio }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-4 text-sm">
                @if($person->profile_link)
                    <a href="{{ $person->profile_link }}" target="_blank" rel="noopener noreferrer" class="underline">Official profile</a>
                @endif
                @if($person->website_url)
                    <a href="{{ $person->website_url }}" target="_blank" rel="noopener" class="underline">Website</a>
                @endif
                @if($person->instagram_url)
                    <a href="{{ $person->instagram_url }}" target="_blank" rel="noopener" class="underline">Instagram</a>
                @endif
                @if($person->linkedin_url)
                    <a href="{{ $person->linkedin_url }}" target="_blank" rel="noopener" class="underline">LinkedIn</a>
                @endif
            </div>

            @if($person->featured_works)
                <div class="mt-10">
                    <h2 class="section-label mb-4">Featured work</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach($person->featured_works as $work)
                            <li class="border-l-2 border-archive-black pl-4">{{ $work }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
