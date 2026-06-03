@extends('layouts.app')

@section('title', $seo['title'] ?? ($heading.' | Ads Of Iraq'))
@section('meta_description', $seo['description'] ?? $intro)

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs text-archive-gray">
        <a href="{{ route('rankings.index') }}" class="underline decoration-neutral-300 underline-offset-4 hover:text-archive-black">Rankings</a>
    </nav>

    <header class="mb-10 sm:mb-12">
        <h1 class="font-display text-3xl tracking-tight text-archive-black sm:text-4xl">{{ $heading }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-archive-gray">{{ $intro }}</p>
    </header>

    <ol class="space-y-4">
        @forelse($people as $index => $person)
            <x-ranking-person-row :person="$person" :rank="$index + 1" />
        @empty
            <li class="border border-archive-border p-6 text-sm text-archive-gray">No ranked people yet. Link directors and editors to campaigns to populate this list.</li>
        @endforelse
    </ol>
</div>
@endsection
