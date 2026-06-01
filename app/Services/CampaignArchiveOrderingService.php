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

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::HOMEPAGE_LATEST_CACHE_KEY);
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
     * Paginate the public archive with manual positions merged into real slots.
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function paginate(
        Builder $baseQuery,
        int $perPage = 24,
        ?int $page = null,
        bool $useManualOrdering = true,
        array $eagerLoads = [],
    ): LengthAwarePaginator {
        $page = $page ?: (int) request()->input('page', 1);

        if (! $useManualOrdering) {
            return $baseQuery->paginate($perPage)->withQueryString();
        }

        $filteredIds = $this->filterOrderedIdsForQuery($baseQuery);
        $total = count($filteredIds);
        $offset = max(0, ($page - 1) * $perPage);
        $sliceIds = array_slice($filteredIds, $offset, $perPage);
        $campaigns = $this->loadCampaignsInOrder($sliceIds, $eagerLoads);

        return new LengthAwarePaginator(
            $campaigns,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * First N public archive campaigns in manual + automatic order.
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function take(Builder $baseQuery, int $limit, array $eagerLoads = []): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        $filteredIds = $this->filterOrderedIdsForQuery($baseQuery);
        $sliceIds = array_slice($filteredIds, 0, $limit);

        return $this->loadCampaignsInOrder($sliceIds, $eagerLoads);
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
     * @return list<int>
     */
    protected function filterOrderedIdsForQuery(Builder $baseQuery): array
    {
        $matchingIds = (clone $baseQuery)->pluck('campaigns.id')->all();
        $matchingLookup = array_fill_keys($matchingIds, true);

        $orderedIds = $this->resolveFullArchiveOrderedIds();

        return array_values(array_filter(
            $orderedIds,
            static fn (int $id) => isset($matchingLookup[$id])
        ));
    }

    /**
     * @return list<int>
     */
    public function resolveFullArchiveOrderedIds(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            $pinnedIds = Campaign::query()
                ->public()
                ->whereNotNull('manual_order')
                ->orderBy('manual_order')
                ->orderByDesc('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $automaticIds = Campaign::query()
                ->public()
                ->automaticArchive()
                ->latestOnPlatform()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return $this->mergeBlockOrder($pinnedIds, $automaticIds);
        });
    }

    /**
     * Manually ordered campaigns first, then automatic campaigns by date.
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

    /**
     * @deprecated Use mergeBlockOrder() for archive ordering.
     *
     * @param  array<int, int>  $pinnedByPosition
     * @param  list<int>  $automaticIds
     * @return list<int>
     */
    public function mergeOrderedIds(array $pinnedByPosition, array $automaticIds): array
    {
        ksort($pinnedByPosition);

        return $this->mergeBlockOrder(array_values($pinnedByPosition), $automaticIds);
    }
}
