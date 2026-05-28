<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Collection;

class CampaignInternalLinksService
{
    /**
     * @return array<string, Collection<int, Campaign>>
     */
    public function groupedRelated(Campaign $campaign, int $perGroup = 4): array
    {
        $excludeId = $campaign->id;
        $base = Campaign::public()->where('id', '!=', $excludeId);

        $groups = [];

        if ($campaign->agencies->isNotEmpty()) {
            $groups['More from '.($campaign->agencies->first()->name ?? 'this agency')] = (clone $base)
                ->whereHas('agencies', fn ($q) => $q->whereIn('agencies.id', $campaign->agencies->pluck('id')))
                ->with(['brands', 'agencies'])
                ->latestOnPlatform()
                ->limit($perGroup)
                ->get();
        }

        if ($campaign->brands->isNotEmpty()) {
            $groups['More from '.($campaign->brands->first()->name ?? 'this brand')] = (clone $base)
                ->whereHas('brands', fn ($q) => $q->whereIn('brands.id', $campaign->brands->pluck('id')))
                ->with(['brands', 'agencies'])
                ->latestOnPlatform()
                ->limit($perGroup)
                ->get();
        }

        if ($campaign->industries->isNotEmpty()) {
            $groups['Same industry'] = (clone $base)
                ->whereHas('industries', fn ($q) => $q->whereIn('industries.id', $campaign->industries->pluck('id')))
                ->with(['brands', 'agencies'])
                ->latestOnPlatform()
                ->limit($perGroup)
                ->get();
        }

        if ($campaign->mediumTypes->isNotEmpty()) {
            $groups['Same category'] = (clone $base)
                ->whereHas('mediumTypes', fn ($q) => $q->whereIn('medium_types.id', $campaign->mediumTypes->pluck('id')))
                ->with(['brands', 'agencies'])
                ->latestOnPlatform()
                ->limit($perGroup)
                ->get();
        }

        if ($campaign->published_at) {
            $groups['Same year'] = (clone $base)
                ->whereYear('published_at', $campaign->published_at->year)
                ->with(['brands', 'agencies'])
                ->latestOnPlatform()
                ->limit($perGroup)
                ->get();
        }

        $groups['Trending on Ads of Iraq'] = (clone $base)
            ->orderByDesc('ranking_score')
            ->with(['brands', 'agencies'])
            ->limit($perGroup)
            ->get();

        return array_filter($groups, fn (Collection $c) => $c->isNotEmpty());
    }
}
