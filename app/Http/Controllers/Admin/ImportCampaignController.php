<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CampaignImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCampaignUrlRequest;
use App\Services\Import\CampaignImporterService;
use App\Services\Import\RepairImportedCampaignMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportCampaignController extends Controller
{
    public function __construct(
        protected CampaignImporterService $importer,
        protected RepairImportedCampaignMedia $mediaRepair,
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

            return back()->with('success', sprintf(
                'Media repair finished for campaign #%d (%d stills added, thumbnail %s).',
                $campaign->id,
                $result['stills_added'],
                $result['thumbnail_updated'] ? 'updated' : 'unchanged',
            ));
        }

        $stats = $this->mediaRepair->repairAll($replace, $request->integer('limit') ?: null);

        return back()->with('success', sprintf(
            'Media repair finished. Repaired: %d, skipped: %d, failed: %d.',
            $stats['repaired'],
            $stats['skipped'],
            $stats['failed'],
        ));
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
