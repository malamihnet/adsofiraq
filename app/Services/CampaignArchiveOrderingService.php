<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CampaignArchiveOrderingService
{
    public const CACHE_KEY = 'archive_campaign_order_ids';

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
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

        $matchingIds = (clone $baseQuery)->pluck('campaigns.id')->all();
        $matchingLookup = array_fill_keys($matchingIds, true);

        $orderedIds = $this->resolveFullArchiveOrderedIds();
        $filteredIds = array_values(array_filter(
            $orderedIds,
            static fn (int $id) => isset($matchingLookup[$id])
        ));

        $total = count($filteredIds);
        $offset = max(0, ($page - 1) * $perPage);
        $sliceIds = array_slice($filteredIds, $offset, $perPage);

        if ($sliceIds === []) {
            return new LengthAwarePaginator(
                collect(),
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $campaigns = Campaign::query()
            ->whereIn('id', $sliceIds)
            ->when($eagerLoads !== [], fn ($q) => $q->with($eagerLoads))
            ->get()
            ->sortBy(fn (Campaign $campaign) => array_search($campaign->id, $sliceIds, true))
            ->values();

        return new LengthAwarePaginator(
            $campaigns,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
