@extends('layouts.app')

@section('title', $award->title . ' — Iraq Creative Awards')
@section('meta_description', $award->description ?? 'Winners and categories for '.$award->title)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <p class="text-sm"><a href="{{ route('awards.index') }}" class="underline">Awards</a></p>
    <h1 class="section-title mt-4">{{ $award->title }}</h1>
    <p class="mt-2 text-archive-gray">{{ $award->year }}</p>
    @if($award->description)
        <p class="mt-6 max-w-2xl leading-relaxed">{{ $award->description }}</p>
    @endif

    <div class="mt-12 space-y-10">
        @foreach($award->categories as $category)
            <section class="border-t border-archive-border pt-8">
                <h2 class="font-display text-2xl">{{ $category->name }}</h2>
                @if($category->description)
                    <p class="mt-2 text-sm text-archive-gray">{{ $category->description }}</p>
                @endif
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @foreach($category->winners as $winner)
                        @if($winner->campaign)
                            <x-campaign-card :campaign="$winner->campaign" />
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
