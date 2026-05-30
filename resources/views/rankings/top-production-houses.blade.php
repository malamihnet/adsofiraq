@extends('layouts.app')

@section('title', 'Top Production Houses — Ads of Iraq')
@section('meta_description', 'Weighted ranking of Iraq’s leading production houses by campaigns, views, saves, and editorial recognition.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <p class="text-sm"><a href="{{ route('rankings.index') }}" class="underline">Rankings</a></p>
    <h1 class="section-title mt-4 mb-4">Top Production Houses</h1>
    <p class="mb-12 max-w-2xl text-sm text-archive-gray">
        Ranked by campaign volume, audience reach, saves, featured work, verification, and recent activity.
    </p>

    <ol class="space-y-6">
        @forelse($agencies as $index => $agency)
            @php
                $preview = $agency->featured_preview_campaign ?? null;
                $campaignCount = $agency->ranking_campaign_count ?? $agency->production_house_campaigns_count ?? 0;
                $totalViews = $agency->ranking_total_views ?? 0;
            @endphp
            <li class="border border-archive-border bg-white">
                <div class="flex flex-col gap-6 p-4 md:flex-row md:items-stretch md:p-6">
                    <div class="flex shrink-0 items-start gap-4 md:w-56">
                        <span class="font-display text-3xl text-archive-gray w-10 pt-1">{{ $index + 1 }}</span>
                        <div class="h-16 w-16 shrink-0 overflow-hidden border border-archive-border bg-archive-light">
                            @if($agency->logo_url)
                                <img src="{{ $agency->logo_url }}" alt="{{ $agency->name }}" class="h-full w-full object-contain p-1">
                            @else
                                <div class="flex h-full w-full items-center justify-center font-display text-xl text-archive-gray">
                                    {{ mb_substr($agency->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('agency.show', $agency) }}" class="font-display text-xl hover:underline inline-flex flex-wrap items-center gap-2">
                            {{ $agency->name }}
                            <x-verified-badge :verified="$agency->is_verified" />
                        </a>
                        <div class="mt-2">
                            <x-agency-role-badges :roles="$agency->roleLabels()" />
                        </div>
                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-archive-gray">
                            <div>
                                <dt class="text-xs uppercase tracking-wider">Campaigns</dt>
                                <dd class="font-medium text-archive-black">{{ number_format($campaignCount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider">Views</dt>
                                <dd class="font-medium text-archive-black">{{ number_format($totalViews) }}</dd>
                            </div>
                            @if(($agency->ranking_featured_campaigns ?? 0) > 0)
                                <div>
                                    <dt class="text-xs uppercase tracking-wider">Featured</dt>
                                    <dd class="font-medium text-archive-black">{{ number_format($agency->ranking_featured_campaigns) }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if(config('app.debug') && isset($agency->ranking_display_score))
                            <p class="mt-2 text-[10px] uppercase tracking-wider text-archive-gray">Score {{ number_format($agency->ranking_display_score, 1) }}</p>
                        @endif
                    </div>

                    @if($preview)
                        <a href="{{ route('campaigns.show', $preview) }}" class="group block w-full shrink-0 md:w-48">
                            <div class="aspect-[4/3] overflow-hidden border border-archive-border bg-archive-light">
                                @if($preview->thumbnail_url)
                                    <x-campaign-image
                                        src="{{ $preview->thumbnail_url }}"
                                        alt="{{ $preview->title }}"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-archive-gray line-clamp-2 group-hover:underline">{{ $preview->title }}</p>
                        </a>
                    @endif
                </div>
            </li>
        @empty
            <li class="border border-archive-border p-8 text-center text-archive-gray">
                No production houses ranked yet. Run <code class="text-xs">php artisan authority:refresh-rankings</code> after campaigns are approved.
            </li>
        @endforelse
    </ol>
</div>
@endsection
