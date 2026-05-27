<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CampaignImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCampaignUrlRequest;
use App\Services\CampaignMediaDeduplicationService;
use App\Services\Import\CampaignImporterService;
use App\Services\Import\RepairImportedCampaignMedia;
use App\Services\PublicStorageSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportCampaignController extends Controller
{
    public function __construct(
        protected CampaignImporterService $importer,
        protected RepairImportedCampaignMedia $mediaRepair,
        protected PublicStorageSyncService $publicStorageSync,
        protected CampaignMediaDeduplicationService $mediaDedup,
    ) {}

    public function create(): View
    {
        return view('admin.import-campaign.create');
    }

    public function store(ImportCampaignUrlRequest $request): RedirectResponse
    {
        $url = $request->input('url');

        try {
            $campaign = $this->importer->import($url, $request->user());

            return redirect()
                ->route('admin.campaigns.edit', $campaign)
                ->with('success', 'Campaign imported successfully as pending.');
        } catch (CampaignImportException $e) {
            return $this->handleImportException($e, $url);
        } catch (\InvalidArgumentException) {
            return back()
                ->withInput()
                ->withErrors(['url' => CampaignImportException::invalidUrl()->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['url' => 'Something went wrong while importing. Please try again or use a different URL.']);
        }
    }

    public function repairMedia(Request $request): RedirectResponse
    {
        $replace = $request->boolean('replace');

        if ($request->filled('campaign_id')) {
            $campaign = \App\Models\Campaign::query()->findOrFail($request->integer('campaign_id'));
            $result = $this->mediaRepair->repair($campaign, $replace);

            return back()->with('success', $this->repairSuccessMessage($campaign->id, $result));
        }

        $stats = $this->mediaRepair->repairAll($replace, $request->integer('limit') ?: null);

        return back()->with('success', sprintf(
            'Media repair finished and public storage synced. Repaired: %d, skipped: %d, failed: %d (%d files copied to public storage).',
            $stats['repaired'],
            $stats['skipped'],
            $stats['failed'],
            $stats['sync_copied'],
        ));
    }

    public function removeDuplicateStills(Request $request): RedirectResponse
    {
        if ($request->filled('campaign_id')) {
            $campaign = \App\Models\Campaign::query()->with('assets')->findOrFail($request->integer('campaign_id'));
            $result = $this->mediaDedup->cleanCampaign(
                $campaign,
                dryRun: false,
                deleteFiles: true,
            );

            return back()->with('success', sprintf(
                'Removed %d duplicate still(s), %d video(s), %d thumbnail-as-still for campaign #%d (%d file(s) deleted).',
                $result['stills_removed'],
                $result['videos_removed'],
                $result['thumbnail_stills_removed'],
                $campaign->id,
                $result['files_deleted'],
            ));
        }

        $stats = $this->mediaDedup->cleanAllCampaigns(
            dryRun: false,
            deleteFiles: true,
            limit: $request->integer('limit') ?: null,
        );

        return back()->with('success', sprintf(
            'Duplicate media cleanup finished. %d campaign(s) affected, %d still(s), %d video(s), %d thumbnail-as-still removed, %d file(s) deleted.',
            $stats['campaigns_affected'],
            $stats['stills_removed'],
            $stats['videos_removed'],
            $stats['thumbnail_stills_removed'],
            $stats['files_deleted'],
        ));
    }

    public function syncPublicStorage(Request $request): RedirectResponse
    {
        $campaignId = $request->filled('campaign_id') ? $request->integer('campaign_id') : null;
        $stats = $this->publicStorageSync->syncAll($campaignId);

        return back()->with('success', 'Media repair finished and public storage synced. '.$this->publicStorageSync->formatStatsMessage($stats));
    }

    /**
     * @param  array{
     *     stills_added: int,
     *     thumbnail_updated: bool,
     *     duplicates_removed?: int,
     *     skipped: bool,
     *     message: ?string,
     *     sync: array{copied: int, skipped: int, failed: int, target: ?string},
     * }  $result
     */
    protected function repairSuccessMessage(int $campaignId, array $result): string
    {
        $message = sprintf(
            'Media repair finished and public storage synced for campaign #%d (%d stills added, %d duplicates removed, thumbnail %s). %s',
            $campaignId,
            $result['stills_added'],
            $result['duplicates_removed'] ?? 0,
            $result['thumbnail_updated'] ? 'updated' : 'unchanged',
            $this->publicStorageSync->formatStatsMessage($result['sync']),
        );

        if (! empty($result['message'])) {
            $message .= ' '.$result['message'];
        }

        return $message;
    }

    protected function handleImportException(CampaignImportException $e, string $url): RedirectResponse
    {
        if ($e->getMessage() === CampaignImportException::alreadyImported()->getMessage()) {
            $existing = $this->importer->findDuplicate($url);

            return back()
                ->withInput()
                ->with('error', $e->getMessage())
                ->with('duplicate_campaign_id', $existing?->id);
        }

        return back()
            ->withInput()
            ->withErrors(['url' => $e->getMessage()]);
    }
}
