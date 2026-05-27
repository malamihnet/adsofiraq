<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaMaintenanceController extends Controller
{
    public function __construct(
        protected CampaignMediaDeduplicationService $mediaDedup,
    ) {}

    public function index(): View
    {
        return view('admin.maintenance.clean-duplicate-media');
    }

    public function cleanDuplicateMedia(Request $request): RedirectResponse
    {
        $dryRun = $request->boolean('dry_run');
        $deleteFiles = $request->boolean('delete_files');
        $campaignId = $request->filled('campaign_id') ? $request->integer('campaign_id') : null;
        $limit = $request->filled('limit') ? $request->integer('limit') : null;

        $stats = $this->mediaDedup->cleanAllCampaigns(
            dryRun: $dryRun,
            deleteFiles: $deleteFiles && ! $dryRun,
            limit: $limit,
            campaignId: $campaignId,
        );

        $mode = $dryRun ? 'Dry run complete' : 'Cleanup complete';

        return back()->with('success', sprintf(
            '%s. Processed %d campaign(s), %d affected. Removed %d duplicate still(s), %d duplicate video(s), %d thumbnail-as-still duplicate(s). %d file(s) deleted from storage.',
            $mode,
            $stats['campaigns_processed'],
            $stats['campaigns_affected'],
            $stats['stills_removed'],
            $stats['videos_removed'],
            $stats['thumbnail_stills_removed'],
            $dryRun ? 0 : $stats['files_deleted'],
        ));
    }

    public function cleanupOrphans(Request $request): RedirectResponse
    {
        $dryRun = $request->boolean('dry_run');
        $campaignId = $request->filled('campaign_id') ? $request->integer('campaign_id') : null;

        $result = $this->mediaDedup->cleanupOrphanFiles(
            dryRun: $dryRun,
            campaignId: $campaignId,
        );

        $mode = $dryRun ? 'Orphan scan complete (dry run)' : 'Orphan cleanup complete';

        return back()->with('success', sprintf(
            '%s. Found %d orphan file(s). %d file(s) removed.',
            $mode,
            count($result['orphans']),
            $dryRun ? 0 : $result['deleted'],
        ));
    }
}
