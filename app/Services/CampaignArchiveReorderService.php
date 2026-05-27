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
     * @param  list<int>  $orderedIds  Campaign IDs in display order (drag order).
     * @param  list<int>  $pinnedIds  Subset that should keep manual archive positions.
     */
    public function saveOrder(array $orderedIds, array $pinnedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));
        $pinnedLookup = array_fill_keys(array_map('intval', $pinnedIds), true);

        DB::transaction(function () use ($orderedIds, $pinnedLookup) {
            $position = 0;

            foreach ($orderedIds as $id) {
                if (! isset($pinnedLookup[$id])) {
                    Campaign::query()
                        ->where('id', $id)
                        ->update(['manual_order' => null]);

                    continue;
                }

                $position++;
                Campaign::query()
                    ->where('id', $id)
                    ->update(['manual_order' => $position]);
            }

            if ($pinnedLookup !== []) {
                Campaign::query()
                    ->approved()
                    ->whereNotNull('manual_order')
                    ->whereNotIn('id', array_keys($pinnedLookup))
                    ->update(['manual_order' => null]);
            }
        });

        $this->archiveOrdering->clearCache();
    }

    public function resetAll(): int
    {
        $count = Campaign::query()->whereNotNull('manual_order')->count();

        Campaign::query()
            ->whereNotNull('manual_order')
            ->update(['manual_order' => null]);

        $this->archiveOrdering->clearCache();

        return $count;
    }

    public function unpin(Campaign $campaign): void
    {
        $campaign->update(['manual_order' => null]);
        $this->archiveOrdering->clearCache();
    }
}
