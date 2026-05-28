@extends('layouts.app')

@section('title', 'Iraq Creative Awards — Ads of Iraq')
@section('meta_description', 'Iraq Creative Awards — celebrating the best in Iraqi advertising, film, design, and craft.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <h1 class="section-title mb-4">Iraq Creative Awards</h1>
    <p class="max-w-2xl text-archive-gray mb-12">Editorial awards for craft, creativity, and impact across Iraqi advertising.</p>

    <div class="grid gap-6 md:grid-cols-2">
        @forelse($awards as $award)
            <a href="{{ route('awards.show', $award) }}" class="block border border-archive-border p-8 hover:bg-archive-cream">
                <p class="text-xs uppercase tracking-widest text-archive-gray">{{ $award->year }}</p>
                <h2 class="mt-2 font-display text-2xl">{{ $award->title }}</h2>
            </a>
        @empty
            <p class="text-archive-gray">Award seasons coming soon.</p>
        @endforelse
    </div>
</div>
@endsection
