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

    public const DELAYED_CACHE_KEY = 'archive_delayed_campaigns';

    public const AUTOMATIC_IDS_CACHE_KEY = 'archive_automatic_campaign_ids';

    /** @deprecated Use DELAYED_CACHE_KEY */
    public const PLACEMENTS_CACHE_KEY = self::DELAYED_CACHE_KEY;

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::HOMEPAGE_LATEST_CACHE_KEY);
        Cache::forget(self::DELAYED_CACHE_KEY);
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
     * Minimum 1-based archive index from admin start page/position.
     */
    public static function computeStartIndex(int $page, int $position, int $perPage): int
    {
        return max(1, (($page - 1) * $perPage) + $position);
    }

    /**
     * Paginate archive with soft delay ordering (latest sort only).
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

        $orderedIds = $this->resolveOrderedIdsForQuery($baseQuery, $perPage);
        $total = count($orderedIds);
        $offset = max(0, ($page - 1) * $perPage);
        $sliceIds = array_slice($orderedIds, $offset, $perPage);
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
     * First N campaigns from archive page 1 (same soft-delay ordering as /campaigns latest).
     *
     * @param  array<int, string>  $eagerLoads
     */
    public function take(Builder $baseQuery, int $limit, int $perPage = 24, array $eagerLoads = []): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        $orderedIds = $this->resolveOrderedIdsForQuery($baseQuery, $perPage);

        return $this->loadCampaignsInOrder(array_slice($orderedIds, 0, $limit), $eagerLoads);
    }

    /**
     * Estimate 1-based archive position for a delayed campaign (default per page 24).
     *
     * @return array{position: int, page: int, slot: int}|null
     */
    public function estimateArchivePosition(int $campaignId, int $perPage = 24): ?array
    {
        $orderedIds = $this->resolveOrderedIdsForQuery(Campaign::public(), $perPage);
        $index = array_search($campaignId, $orderedIds, true);

        if ($index === false) {
            return null;
        }

        $position = $index + 1;

        return [
            'position' => $position,
            'page' => (int) floor(($position - 1) / $perPage) + 1,
            'slot' => (($position - 1) % $perPage) + 1,
        ];
    }

    /**
     * @return list<int>
     */
    public function resolveOrderedIdsForQuery(Builder $baseQuery, int $perPage): array
    {
        $matchingIds = (clone $baseQuery)->pluck('campaigns.id')->map(fn ($id) => (int) $id)->all();
        $matchingLookup = array_fill_keys($matchingIds, true);

        $automaticIds = $this->filterAutomaticIdsForMatching($matchingLookup);
        $delayed = $this->filterDelayedForMatching($matchingLookup, $perPage);

        return $this->mergeDelayedIntoArchiveOrder($automaticIds, $delayed);
    }

    /**
     * Insert delayed campaigns at minimum start index; newer automatic items push them down.
     *
     * @param  list<int>  $automaticIds
     * @param  list<array{id: int, start_index: int, approved_at: int, sort_id: int}>  $delayed
     * @return list<int>
     */
    public function mergeDelayedIntoArchiveOrder(array $automaticIds, array $delayed): array
    {
        $result = $automaticIds;

        usort($delayed, static function (array $a, array $b): int {
            if ($a['start_index'] !== $b['start_index']) {
                return $a['start_index'] <=> $b['start_index'];
            }

            if ($a['approved_at'] !== $b['approved_at']) {
                return $b['approved_at'] <=> $a['approved_at'];
            }

            return $b['sort_id'] <=> $a['sort_id'];
        });

        foreach ($delayed as $item) {
            $id = $item['id'];
            $result = array_values(array_filter($result, static fn (int $existingId) => $existingId !== $id));
            $insertAt = min(max(0, $item['start_index'] - 1), count($result));
            array_splice($result, $insertAt, 0, [$id]);
        }

        return array_values($result);
    }

    /**
     * First N public archive campaigns in automatic latest order (ignores archive delay).
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
     * @param  array<int, true>  $matchingLookup
     * @return list<array{id: int, start_index: int, approved_at: int, sort_id: int}>
     */
    protected function filterDelayedForMatching(array $matchingLookup, int $perPage): array
    {
        return array_values(array_filter(
            $this->resolveDelayedCampaigns($perPage),
            static fn (array $item) => isset($matchingLookup[$item['id']]),
        ));
    }

    /**
     * @return list<array{id: int, start_index: int, approved_at: int, sort_id: int}>
     */
    protected function resolveDelayedCampaigns(int $perPage): array
    {
        $raw = Cache::remember(self::DELAYED_CACHE_KEY, now()->addHour(), function () {
            return Campaign::query()
                ->public()
                ->archivePlaced()
                ->orderBy('archive_page')
                ->orderBy('archive_position')
                ->get(['id', 'archive_page', 'archive_position', 'approved_at'])
                ->map(fn (Campaign $campaign) => [
                    'id' => (int) $campaign->id,
                    'archive_page' => (int) $campaign->archive_page,
                    'archive_position' => (int) $campaign->archive_position,
                    'approved_at' => $campaign->approved_at?->getTimestamp() ?? 0,
                    'sort_id' => (int) $campaign->id,
                ])
                ->all();
        });

        return array_map(static function (array $item) use ($perPage): array {
            return [
                'id' => $item['id'],
                'start_index' => self::computeStartIndex($item['archive_page'], $item['archive_position'], $perPage),
                'approved_at' => $item['approved_at'],
                'sort_id' => $item['sort_id'],
            ];
        }, $raw);
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
     * @return list<int>
     */
    public function resolveAutomaticArchiveIds(): array
    {
        return Cache::remember(self::AUTOMATIC_IDS_CACHE_KEY, now()->addHour(), function () {
            $delayedIds = Campaign::query()
                ->public()
                ->archivePlaced()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $delayedLookup = array_fill_keys($delayedIds, true);

            return Campaign::query()
                ->public()
                ->latestOnPlatform()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(static fn (int $id) => isset($delayedLookup[$id]))
                ->values()
                ->all();
        });
    }

    /**
     * @return list<int>
     */
    public function resolveFullArchiveOrderedIds(): array
    {
        return $this->resolveOrderedIdsForQuery(Campaign::public(), 24);
    }

    /**
     * @deprecated Fixed-slot merge removed.
     *
     * @param  list<int>  $pinnedIds
     * @param  list<int>  $automaticIds
     * @return list<int>
     */
    public function mergeBlockOrder(array $pinnedIds, array $automaticIds): array
    {
        return array_values(array_unique(array_merge($pinnedIds, $automaticIds)));
    }
}
