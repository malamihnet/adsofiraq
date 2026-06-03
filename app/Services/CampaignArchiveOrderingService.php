<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CampaignArchiveOrderingService
{
    public const CACHE_KEY = 'archive_campaign_order_ids';

    public const HOMEPAGE_LATEST_CACHE_KEY = 'homepage_latest_campaign_ids';

    public const PLACEMENTS_CACHE_KEY = 'archive_placement_map';

    public const AUTOMATIC_IDS_CACHE_KEY = 'archive_automatic_campaign_ids';

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::HOMEPAGE_LATEST_CACHE_KEY);
        Cache::forget(self::PLACEMENTS_CACHE_KEY);
        Cache::forget(self::AUTOMATIC_IDS_CACHE_KEY);
        Cache::forget('hero_campaigns');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function clearCacheAndLog(string $reason = 'archive_order_saved', array $context = []): void
    {
        $this->clearCache();

        Log::info('Archive order saved and caches cleared.', array_merge(['reason' => $reason], $context));
    }

    /**
     * Paginate the public archive with page/position placements merged into slots.
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function paginate(
        Builder $baseQuery,
        int $perPage = 24,
        ?int $page = null,
        bool $usePlacementOrdering = true,
        array $eagerLoads = [],
    ): LengthAwarePaginator {
        $page = $page ?: (int) request()->input('page', 1);

        if (! $usePlacementOrdering) {
            return $baseQuery->paginate($perPage)->withQueryString();
        }

        $matchingIds = (clone $baseQuery)->pluck('campaigns.id')->map(fn ($id) => (int) $id)->all();
        $matchingLookup = array_fill_keys($matchingIds, true);

        $placementsByPage = $this->filterPlacementsForMatching($matchingLookup);
        $automaticIds = $this->filterAutomaticIdsForMatching($matchingLookup);

        $placedMatchingCount = $this->countMatchingPlacements($placementsByPage);
        $total = $placedMatchingCount + count($automaticIds);

        $pageIds = $this->buildPageIds($placementsByPage, $automaticIds, $page, $perPage);
        $campaigns = $this->loadCampaignsInOrder($pageIds, $eagerLoads);

        return new LengthAwarePaginator(
            $campaigns,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * First N campaigns from archive page 1 (same ordering as /campaigns latest, default per page 24).
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function take(Builder $baseQuery, int $limit, int $perPage = 24, array $eagerLoads = []): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        $matchingIds = (clone $baseQuery)->pluck('campaigns.id')->map(fn ($id) => (int) $id)->all();
        $matchingLookup = array_fill_keys($matchingIds, true);

        $placementsByPage = $this->filterPlacementsForMatching($matchingLookup);
        $automaticIds = $this->filterAutomaticIdsForMatching($matchingLookup);

        $pageIds = $this->buildPageIds($placementsByPage, $automaticIds, 1, $perPage);
        $sliceIds = array_slice($pageIds, 0, $limit);

        return $this->loadCampaignsInOrder($sliceIds, $eagerLoads);
    }

    /**
     * First N public archive campaigns in automatic latest order (ignores archive placement).
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function takeAutomaticLatest(Builder $baseQuery, int $limit, array $eagerLoads = []): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        $ids = (clone $baseQuery)
            ->latestOnPlatform()
            ->limit($limit)
            ->pluck('campaigns.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->loadCampaignsInOrder($ids, $eagerLoads);
    }

    /**
     * @param  array<int, string>  $eagerLoads
     */
    protected function loadCampaignsInOrder(array $orderedIds, array $eagerLoads = []): Collection
    {
        if ($orderedIds === []) {
            return collect();
        }

        return Campaign::query()
            ->whereIn('id', $orderedIds)
            ->when($eagerLoads !== [], fn ($q) => $q->with($eagerLoads))
            ->get()
            ->sortBy(fn (Campaign $campaign) => array_search($campaign->id, $orderedIds, true))
            ->values();
    }

    /**
     * Build ordered IDs for one archive page (1-based page number).
     *
     * @param  array<int, array<int, int>>  $placementsByPage  page => [position => campaignId]
     * @param  list<int>  $automaticIds
     * @return list<int>
     */
    public function buildPageIds(array $placementsByPage, array $automaticIds, int $page, int $perPage): array
    {
        if ($page < 1 || $perPage < 1) {
            return [];
        }

        $autoOffset = 0;

        for ($p = 1; $p < $page; $p++) {
            $placedOnPage = count($placementsByPage[$p] ?? []);
            $autoOffset += max(0, $perPage - $placedOnPage);
        }

        $slots = array_fill(0, $perPage, null);
        $pagePlacements = $placementsByPage[$page] ?? [];

        foreach ($pagePlacements as $position => $campaignId) {
            $index = $position - 1;
            if ($index >= 0 && $index < $perPage) {
                $slots[$index] = $campaignId;
            }
        }

        $emptyIndices = [];
        foreach ($slots as $index => $campaignId) {
            if ($campaignId === null) {
                $emptyIndices[] = $index;
            }
        }

        $autoSlice = array_slice($automaticIds, $autoOffset, count($emptyIndices));

        foreach ($emptyIndices as $i => $slotIndex) {
            if (isset($autoSlice[$i])) {
                $slots[$slotIndex] = $autoSlice[$i];
            }
        }

        return array_values(array_filter(
            $slots,
            static fn ($id) => $id !== null,
        ));
    }

    /**
     * @param  array<int, true>  $matchingLookup
     * @return array<int, array<int, int>>
     */
    protected function filterPlacementsForMatching(array $matchingLookup): array
    {
        $map = $this->resolvePlacementMap();
        $filtered = [];

        foreach ($map as $page => $positions) {
            foreach ($positions as $position => $campaignId) {
                if (isset($matchingLookup[$campaignId])) {
                    $filtered[$page][$position] = $campaignId;
                }
            }
        }

        ksort($filtered);

        return $filtered;
    }

    /**
     * @param  array<int, true>  $matchingLookup
     * @return list<int>
     */
    protected function filterAutomaticIdsForMatching(array $matchingLookup): array
    {
        return array_values(array_filter(
            $this->resolveAutomaticArchiveIds(),
            static fn (int $id) => isset($matchingLookup[$id]),
        ));
    }

    /**
     * @param  array<int, array<int, int>>  $placementsByPage
     */
    protected function countMatchingPlacements(array $placementsByPage): int
    {
        $count = 0;

        foreach ($placementsByPage as $positions) {
            $count += count($positions);
        }

        return $count;
    }

    /**
     * @return array<int, array<int, int>>  page => [position => campaignId]
     */
    public function resolvePlacementMap(): array
    {
        return Cache::remember(self::PLACEMENTS_CACHE_KEY, now()->addHour(), function () {
            $map = [];

            Campaign::query()
                ->public()
                ->archivePlaced()
                ->orderBy('archive_page')
                ->orderBy('archive_position')
                ->get(['id', 'archive_page', 'archive_position'])
                ->each(function (Campaign $campaign) use (&$map) {
                    $page = (int) $campaign->archive_page;
                    $position = (int) $campaign->archive_position;
                    $map[$page][$position] = (int) $campaign->id;
                });

            return $map;
        });
    }

    /**
     * @return list<int>
     */
    public function resolveAutomaticArchiveIds(): array
    {
        return Cache::remember(self::AUTOMATIC_IDS_CACHE_KEY, now()->addHour(), function () {
            $placedIds = Campaign::query()
                ->public()
                ->archivePlaced()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $placedLookup = array_fill_keys($placedIds, true);

            return Campaign::query()
                ->public()
                ->latestOnPlatform()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(static fn (int $id) => isset($placedLookup[$id]))
                ->values()
                ->all();
        });
    }

    /**
     * @deprecated No longer used; manual_order archive ordering was removed.
     *
     * @return list<int>
     */
    public function resolveFullArchiveOrderedIds(): array
    {
        return $this->resolveAutomaticArchiveIds();
    }

    /**
     * @deprecated
     *
     * @param  list<int>  $pinnedIds
     * @param  list<int>  $automaticIds
     * @return list<int>
     */
    public function mergeBlockOrder(array $pinnedIds, array $automaticIds): array
    {
        $pinnedLookup = array_fill_keys($pinnedIds, true);
        $automaticFiltered = array_values(array_filter(
            $automaticIds,
            static fn (int $id) => ! isset($pinnedLookup[$id]),
        ));

        return array_values(array_unique(array_merge($pinnedIds, $automaticFiltered)));
    }
}
