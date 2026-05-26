@extends('layouts.app')

@section('title', 'Agencies — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <h1 class="section-title mb-12">Agencies</h1>
    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @foreach($agencies as $agency)
            <a href="{{ route('agencies.show', $agency) }}" class="border border-archive-border p-6 hover:border-archive-black">
                <p class="font-display text-lg">{{ $agency->name }}</p>
                <p class="mt-2 text-xs text-archive-gray">{{ $agency->campaigns_count }} campaigns</p>
            </a>
        @endforeach
    </div>
    <div class="mt-12">{{ $agencies->links() }}</div>
</div>
@endsection
