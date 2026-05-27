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
            $pinned = Campaign::query()
                ->public()
                ->whereNotNull('manual_order')
                ->orderBy('manual_order')
                ->orderByDesc('id')
                ->get(['id', 'manual_order']);

            $pinnedByPosition = [];
            foreach ($pinned as $campaign) {
                $pinnedByPosition[(int) $campaign->manual_order] = (int) $campaign->id;
            }

            $automaticIds = Campaign::query()
                ->public()
                ->automaticArchive()
                ->latestOnPlatform()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return $this->mergeOrderedIds($pinnedByPosition, $automaticIds);
        });
    }

    /**
     * Merge pinned positions with automatic campaigns filling gaps.
     *
     * @param  array<int, int>  $pinnedByPosition  position => campaign_id
     * @param  list<int>  $automaticIds
     * @return list<int>
     */
    public function mergeOrderedIds(array $pinnedByPosition, array $automaticIds): array
    {
        $result = [];
        $autoIndex = 0;
        $position = 1;
        $maxPinnedPosition = $pinnedByPosition === [] ? 0 : max(array_keys($pinnedByPosition));

        while ($autoIndex < count($automaticIds) || isset($pinnedByPosition[$position]) || $position <= $maxPinnedPosition) {
            if (isset($pinnedByPosition[$position])) {
                $result[] = $pinnedByPosition[$position];
            } elseif ($autoIndex < count($automaticIds)) {
                $result[] = $automaticIds[$autoIndex];
                $autoIndex++;
            }

            $position++;

            if ($position > count($automaticIds) + count($pinnedByPosition) + 100_000) {
                break;
            }
        }

        return array_values(array_unique($result));
    }
}
