<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CampaignImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCampaignUrlRequest;
use App\Services\Import\CampaignImporterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImportCampaignController extends Controller
{
    public function __construct(
        protected CampaignImporterService $importer,
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
