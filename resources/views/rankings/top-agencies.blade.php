@extends('layouts.app')

@section('title', 'Top Agencies in Iraq — Ads of Iraq')
@section('meta_description', 'Ranked list of Iraq’s leading advertising agencies by campaign quality, engagement, and editorial recognition.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <p class="text-sm"><a href="{{ route('rankings.index') }}" class="underline">Rankings</a></p>
    <h1 class="section-title mt-4 mb-12">Top Agencies — Iraq</h1>

    <ol class="space-y-4">
        @foreach($agencies as $index => $agency)
            <li class="flex items-center gap-6 border border-archive-border p-4">
                <span class="font-display text-3xl text-archive-gray w-10">{{ $index + 1 }}</span>
                <div class="flex-1">
                    <a href="{{ route('agency.show', $agency) }}" class="font-display text-xl hover:underline inline-flex items-center gap-2">
                        {{ $agency->name }}
                        <x-verified-badge :verified="$agency->is_verified" />
                    </a>
                    <p class="text-sm text-archive-gray mt-1">{{ $agency->campaigns_count }} campaigns · Score {{ number_format($agency->ranking_score, 0) }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</div>
@endsection
