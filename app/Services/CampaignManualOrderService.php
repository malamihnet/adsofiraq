<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignManualOrderService
{
    public function __construct(
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    public function syncFromRequest(Campaign $campaign, bool $enabled, ?int $position): void
    {
        if ($campaign->status !== 'approved' || ! $enabled || $position === null) {
            if ($campaign->manual_order !== null) {
                $campaign->manual_order = null;
                $campaign->saveQuietly();
                $this->archiveOrdering->clearCache();
            }

            return;
        }

        DB::transaction(function () use ($campaign, $position) {
            $this->shiftOccupiedSlots($campaign, $position);
            $campaign->manual_order = $position;
            $campaign->saveQuietly();
        });

        $this->archiveOrdering->clearCache();
    }

    protected function shiftOccupiedSlots(Campaign $campaign, int $position): void
    {
        $occupant = Campaign::query()
            ->approved()
            ->where('manual_order', $position)
            ->where('id', '!=', $campaign->id)
            ->first();

        if (! $occupant) {
            return;
        }

        Campaign::query()
            ->approved()
            ->whereNotNull('manual_order')
            ->where('manual_order', '>=', $position)
            ->where('id', '!=', $campaign->id)
            ->orderByDesc('manual_order')
            ->each(function (Campaign $other) {
                $other->updateQuietly(['manual_order' => (int) $other->manual_order + 1]);
            });
    }
}
