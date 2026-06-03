<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Validation\ValidationException;

class CampaignArchivePlacementService
{
    public const MAX_POSITION = 100;

    public function __construct(
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    /**
     * @throws ValidationException
     */
    public function applyToCampaign(Campaign $campaign, bool $enabled, ?int $page, ?int $position): void
    {
        if (! $enabled) {
            $campaign->update([
                'archive_placement_enabled' => false,
                'archive_page' => null,
                'archive_position' => null,
            ]);

            $this->archiveOrdering->clearCacheAndLog('archive_placement_disabled', [
                'campaign_id' => $campaign->id,
            ]);

            return;
        }

        if ($campaign->status !== 'approved') {
            throw ValidationException::withMessages([
                'archive_placement_enabled' => 'Only approved campaigns can use custom archive placement.',
            ]);
        }

        $page = (int) $page;
        $position = (int) $position;

        $conflict = $this->findSlotConflict($campaign->id, $page, $position);

        if ($conflict) {
            throw ValidationException::withMessages([
                'archive_position' => sprintf(
                    'This archive slot is already used by campaign "%s".',
                    $conflict->title,
                ),
            ]);
        }

        $campaign->update([
            'archive_placement_enabled' => true,
            'archive_page' => $page,
            'archive_position' => $position,
        ]);

        $this->archiveOrdering->clearCacheAndLog('archive_placement_saved', [
            'campaign_id' => $campaign->id,
            'archive_page' => $page,
            'archive_position' => $position,
        ]);
    }

    public function removePlacement(Campaign $campaign): void
    {
        $campaign->update([
            'archive_placement_enabled' => false,
            'archive_page' => null,
            'archive_position' => null,
        ]);

        $this->archiveOrdering->clearCacheAndLog('archive_placement_removed', [
            'campaign_id' => $campaign->id,
        ]);
    }

    public function clearAllPlacements(): int
    {
        $count = Campaign::query()
            ->where('archive_placement_enabled', true)
            ->count();

        Campaign::query()
            ->where('archive_placement_enabled', true)
            ->update([
                'archive_placement_enabled' => false,
                'archive_page' => null,
                'archive_position' => null,
            ]);

        $this->archiveOrdering->clearCacheAndLog('archive_placements_cleared', [
            'count' => $count,
        ]);

        return $count;
    }

    public function clearLegacyManualOrder(): int
    {
        $count = Campaign::query()->whereNotNull('manual_order')->count();

        Campaign::query()
            ->whereNotNull('manual_order')
            ->update(['manual_order' => null]);

        $this->archiveOrdering->clearCacheAndLog('legacy_manual_order_cleared', [
            'count' => $count,
        ]);

        return $count;
    }

    protected function findSlotConflict(int $campaignId, int $page, int $position): ?Campaign
    {
        return Campaign::query()
            ->where('archive_placement_enabled', true)
            ->where('archive_page', $page)
            ->where('archive_position', $position)
            ->where('id', '!=', $campaignId)
            ->first();
    }
}
