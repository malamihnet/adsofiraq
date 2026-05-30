@extends('layouts.app')

@section('title', 'Top Production Houses — Ads of Iraq')
@section('meta_description', 'Weighted ranking of Iraq’s leading production houses by campaigns, views, saves, and editorial recognition.')

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs text-archive-gray">
        <a href="{{ route('rankings.index') }}" class="underline decoration-neutral-300 underline-offset-4 hover:text-archive-black">Rankings</a>
    </nav>

    <header class="mb-10 sm:mb-12">
        <h1 class="font-display text-3xl tracking-tight text-archive-black sm:text-4xl">Top Production Houses</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-archive-gray">
            Ranked by campaign volume, audience reach, saves, featured work, verification, and recent activity.
        </p>
    </header>

    <ol class="space-y-4">
        @forelse($agencies as $index => $agency)
            <x-ranking-agency-row
                :agency="$agency"
                :rank="$index + 1"
                :campaign-count="$agency->ranking_campaign_count ?? $agency->production_house_campaigns_count ?? 0"
                :total-views="$agency->ranking_total_views ?? 0"
                :featured-count="$agency->ranking_featured_campaigns ?? 0"
                :preview="$agency->featured_preview_campaign ?? null"
            />
        @empty
            <li class="rounded-2xl border border-dashed border-neutral-200 py-16 text-center text-sm text-archive-gray">
                No production houses ranked yet. Run <code class="text-xs">php artisan authority:refresh-rankings</code> after campaigns are approved.
            </li>
        @endforelse
    </ol>
</div>
@endsection
