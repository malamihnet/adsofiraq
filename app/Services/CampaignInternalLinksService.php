<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Collection;

class CampaignInternalLinksService
{
    /**
     * @return array<string, Collection<int, Campaign>>
     */
    public function groupedRelated(Campaign $campaign, int $perGroup = 6): array
    {
        $excludeIds = [$campaign->id];
        $base = Campaign::public()->where('id', '!=', $campaign->id);
        $groups = [];

        if ($campaign->agencies->isNotEmpty()) {
            $agency = $campaign->agencies->first();
            $collection = $this->fetchGroup(
                (clone $base)->whereHas('agencies', fn ($q) => $q->whereIn('agencies.id', $campaign->agencies->pluck('id'))),
                $excludeIds,
                $perGroup,
            );

            if ($collection->isNotEmpty()) {
                $groups['More from '.($agency->name ?? 'this agency')] = $collection;
            }
        }

        if ($campaign->brands->isNotEmpty()) {
            $brand = $campaign->brands->first();
            $collection = $this->fetchGroup(
                (clone $base)->whereHas('brands', fn ($q) => $q->whereIn('brands.id', $campaign->brands->pluck('id'))),
                $excludeIds,
                $perGroup,
            );

            if ($collection->isNotEmpty()) {
                $groups['More from '.($brand->name ?? 'this brand')] = $collection;
            }
        }

        if ($campaign->mediumTypes->isNotEmpty()) {
            $medium = $campaign->mediumTypes->first();
            $collection = $this->fetchGroup(
                (clone $base)->whereHas('mediumTypes', fn ($q) => $q->whereIn('medium_types.id', $campaign->mediumTypes->pluck('id'))),
                $excludeIds,
                $perGroup,
            );

            if ($collection->isNotEmpty()) {
                $groups['More in '.($medium->name ?? 'this category')] = $collection;
            }
        } elseif ($campaign->industries->isNotEmpty()) {
            $industry = $campaign->industries->first();
            $collection = $this->fetchGroup(
                (clone $base)->whereHas('industries', fn ($q) => $q->whereIn('industries.id', $campaign->industries->pluck('id'))),
                $excludeIds,
                $perGroup,
            );

            if ($collection->isNotEmpty()) {
                $groups['Same industry: '.($industry->name ?? 'related work')] = $collection;
            }
        }

        return $groups;
    }

    /**
     * @param  list<int>  $excludeIds
     */
    protected function fetchGroup($query, array &$excludeIds, int $limit): Collection
    {
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $results = $query
            ->with(['brands', 'agencies', 'productionHouses', 'mediumTypes'])
            ->latestOnPlatform()
            ->limit($limit)
            ->get();

        foreach ($results as $item) {
            $excludeIds[] = $item->id;
        }

        return $results;
    }
}
