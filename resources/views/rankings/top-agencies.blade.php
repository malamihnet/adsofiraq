@extends('layouts.app')

@section('title', 'Top Agencies in Iraq — Ads of Iraq')
@section('meta_description', 'Ranked list of Iraq’s leading advertising agencies by campaign quality, engagement, and editorial recognition.')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs text-archive-gray">
        <a href="{{ route('rankings.index') }}" class="underline decoration-neutral-300 underline-offset-4 hover:text-archive-black">Rankings</a>
    </nav>

    <header class="mb-10 sm:mb-12">
        <h1 class="font-display text-3xl tracking-tight text-archive-black sm:text-4xl">Top Agencies — Iraq</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-archive-gray">
            Leading agencies by approved campaigns, engagement, and platform recognition.
        </p>
    </header>

    <ol class="space-y-4">
        @foreach($agencies as $index => $agency)
            <x-ranking-agency-row
                :agency="$agency"
                :rank="$index + 1"
                :campaign-count="$agency->agency_campaigns_count ?? 0"
                :total-views="$agency->ranking_total_views ?? 0"
            />
        @endforeach
    </ol>
</div>
@endsection
