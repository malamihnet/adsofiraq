@extends('layouts.app')

@section('title', $person->name . ' — Ads of Iraq')
@section('meta_description', $person->seo_description)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="grid gap-12 border-b border-archive-border pb-12 lg:grid-cols-[320px_1fr]">
        <div>
            <div class="aspect-[4/5] overflow-hidden border border-archive-border bg-archive-light">
                <img src="{{ $person->photo_url }}" alt="{{ $person->name }}" class="h-full w-full object-cover">
            </div>
        </div>
        <div>
            <h1 class="font-display text-3xl md:text-4xl inline-flex items-center gap-2 flex-wrap">
                {{ $person->name }}
                <x-verified-badge :verified="$person->is_verified" />
            </h1>
            <p class="mt-2 text-sm uppercase tracking-widest text-archive-gray">{{ $person->position }}</p>

            @if($person->bio)
                <p class="mt-8 max-w-2xl leading-relaxed">{{ $person->bio }}</p>
            @endif

            @if($person->profile_link)
                <a href="{{ $person->profile_link }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-block text-sm underline">
                    Official profile
                </a>
            @endif

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
