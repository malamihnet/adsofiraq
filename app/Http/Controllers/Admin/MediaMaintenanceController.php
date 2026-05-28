<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CampaignMediaDeduplicationService;
use App\Services\Import\CleanNonGalleryImportedStillsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaMaintenanceController extends Controller
{
    public function __construct(
        protected CampaignMediaDeduplicationService $mediaDedup,
        protected CleanNonGalleryImportedStillsService $nonGalleryCleaner,
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

    public function cleanNonGalleryStills(Request $request): RedirectResponse
    {
        $dryRun = $request->boolean('dry_run');
        $deleteFiles = $request->boolean('delete_files');
        $campaignId = $request->filled('campaign_id') ? $request->integer('campaign_id') : null;
        $limit = $request->filled('limit') ? $request->integer('limit') : null;

        if ($campaignId) {
            $campaign = \App\Models\Campaign::query()->findOrFail($campaignId);
            $result = $this->nonGalleryCleaner->cleanCampaign($campaign, $dryRun, $deleteFiles && ! $dryRun);

            if ($result['skipped']) {
                return back()->with('error', $result['message'] ?? 'Could not clean this campaign.');
            }

            $mode = $dryRun ? 'Dry run complete' : 'Clean Non-Gallery Stills complete';

            return back()->with('success', sprintf(
                '%s for campaign #%d. Removed %d non-gallery still(s) (%d file(s) deleted). Source page has %d gallery image(s).',
                $mode,
                $campaign->id,
                $result['stills_removed'],
                $dryRun ? 0 : $result['files_deleted'],
                $result['gallery_urls'],
            ));
        }

        $stats = $this->nonGalleryCleaner->cleanAll($dryRun, $deleteFiles && ! $dryRun, $limit);

        $mode = $dryRun ? 'Dry run complete' : 'Clean Non-Gallery Stills complete';

        return back()->with('success', sprintf(
            '%s. Processed %d campaign(s), %d affected, %d skipped. Removed %d still(s), %d file(s) deleted.',
            $mode,
            $stats['campaigns_processed'],
            $stats['campaigns_affected'],
            $stats['skipped'],
            $stats['stills_removed'],
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
