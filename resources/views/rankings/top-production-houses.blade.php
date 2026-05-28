@extends('layouts.app')

@section('title', 'Top Production Houses — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <p class="text-sm"><a href="{{ route('rankings.index') }}" class="underline">Rankings</a></p>
    <h1 class="section-title mt-4 mb-12">Top Production Houses</h1>
    <ol class="space-y-4">
        @forelse($agencies as $index => $agency)
            <li class="flex items-center gap-6 border border-archive-border p-4">
                <span class="font-display text-3xl text-archive-gray w-10">{{ $index + 1 }}</span>
                <a href="{{ route('agency.show', $agency) }}" class="font-display text-xl hover:underline">{{ $agency->name }}</a>
            </li>
        @empty
            <li class="text-archive-gray">No production houses ranked yet.</li>
        @endforelse
    </ol>
</div>
@endsection
