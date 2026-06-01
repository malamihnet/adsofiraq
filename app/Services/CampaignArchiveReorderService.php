<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignArchiveReorderService
{
    public function __construct(
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    /**
     * Persist the admin reorder list as manual archive positions (1-based).
     * Every ID in $orderedIds is pinned in that sequence; other manual pins are cleared.
     *
     * @param  list<int>  $orderedIds  Campaign IDs in display order (DOM / drag order).
     */
    public function saveOrder(array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        DB::transaction(function () use ($orderedIds) {
            $position = 0;

            foreach ($orderedIds as $id) {
                $position++;
                Campaign::query()
                    ->where('id', $id)
                    ->update(['manual_order' => $position]);
            }

            Campaign::query()
                ->approved()
                ->whereNotNull('manual_order')
                ->when(
                    $orderedIds !== [],
                    fn ($q) => $q->whereNotIn('id', $orderedIds),
                )
                ->when(
                    $orderedIds === [],
                    fn ($q) => $q,
                )
                ->update(['manual_order' => null]);
        });

        $this->archiveOrdering->clearCacheAndLog('archive_reorder_saved', [
            'campaign_count' => count($orderedIds),
        ]);
    }

    public function resetAll(): int
    {
        $count = Campaign::query()->whereNotNull('manual_order')->count();

        Campaign::query()
            ->whereNotNull('manual_order')
            ->update(['manual_order' => null]);

        $this->archiveOrdering->clearCacheAndLog('archive_reorder_reset');

        return $count;
    }

    public function unpin(Campaign $campaign): void
    {
        $campaign->update(['manual_order' => null]);
        $this->archiveOrdering->clearCacheAndLog('archive_unpin');
    }
}
