<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArchivePlacementRequest;
use App\Models\Campaign;
use App\Services\CampaignArchiveOrderingService;
use App\Services\CampaignArchivePlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArchivePlacementController extends Controller
{
    public function __construct(
        protected CampaignArchivePlacementService $placementService,
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    public function index(): View
    {
        $perPage = 24;

        $campaigns = Campaign::query()
            ->archivePlaced()
            ->with(['brands', 'agencies'])
            ->orderBy('archive_page')
            ->orderBy('archive_position')
            ->get()
            ->map(function (Campaign $campaign) use ($perPage) {
                $estimate = $this->archiveOrdering->estimateArchivePosition($campaign->id, $perPage);
                $campaign->setAttribute('estimated_archive_page', $estimate['page'] ?? null);
                $campaign->setAttribute('estimated_archive_slot', $estimate['slot'] ?? null);

                return $campaign;
            });

        return view('admin.archive-placements.index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function store(StoreArchivePlacementRequest $request): RedirectResponse
    {
        $campaign = Campaign::query()->findOrFail($request->integer('campaign_id'));

        $this->placementService->applyToCampaign(
            $campaign,
            enabled: true,
            page: $request->integer('archive_page'),
            position: $request->integer('archive_position'),
        );

        return redirect()
            ->route('admin.archive-placements.index')
            ->with('success', sprintf(
                'Archive delay saved: %s will not appear before page %d, position %d.',
                $campaign->title,
                $request->integer('archive_page'),
                $request->integer('archive_position'),
            ));
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->placementService->removePlacement($campaign);

        return redirect()
            ->route('admin.archive-placements.index')
            ->with('success', 'Archive delay removed.');
    }

    public function clearAll(): RedirectResponse
    {
        $cleared = $this->placementService->clearAllPlacements();

        return redirect()
            ->route('admin.archive-placements.index')
            ->with('success', sprintf('Cleared archive delay for %d campaign(s).', $cleared));
    }

    public function clearLegacyManualOrder(): RedirectResponse
    {
        $cleared = $this->placementService->clearLegacyManualOrder();

        return redirect()
            ->route('admin.archive-placements.index')
            ->with('success', sprintf('Cleared legacy manual_order on %d campaign(s).', $cleared));
    }
}
