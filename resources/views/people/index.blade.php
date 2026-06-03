@extends('layouts.app')

@section('title', 'People | Ads Of Iraq')
@section('meta_description', 'Discover creative professionals from Iraq\'s advertising, film, design, and production industry.')

@push('meta')
    <link rel="canonical" href="{{ route('people.index') }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <x-breadcrumbs :items="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'People', 'url' => null],
    ]" />
    <header class="mb-12 flex flex-col gap-6 border-b border-archive-border pb-10 md:flex-row md:items-end md:justify-between">
        <div class="max-w-2xl">
            <h1 class="section-title">People</h1>
            <p class="mt-4 text-archive-gray leading-relaxed">
                Directors, producers, photographers, editors, artists, and production crew from Iraq and the region.
            </p>
        </div>
        <a href="{{ route('people.apply') }}" class="btn-outline shrink-0 text-xs">Apply for listing</a>
    </header>

    <form method="GET" action="{{ route('people.index') }}" class="mb-8">
        <div class="flex flex-wrap gap-4">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search people, positions, roles..."
                class="input-field min-w-[200px] flex-1"
            >
            <button type="submit" class="btn-primary">Search</button>
        </div>
    </form>

    @if($people->count())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($people as $person)
                <x-person-card :person="$person" />
            @endforeach
        </div>
        <div class="mt-12">
            {{ $people->links() }}
        </div>
    @else
        <p class="text-archive-gray">No profiles published yet. Check back soon.</p>
    @endif
</div>
@endsection
