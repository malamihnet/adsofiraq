<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportAdsOfWorldRequest;
use App\Models\ImportBatch;
use App\Services\Import\AdsOfTheWorldCountryCrawler;
use App\Services\Import\BulkImportProcessor;
use App\Services\Import\ImportBatchDeleteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportAdsOfWorldController extends Controller
{
    public function __construct(
        protected AdsOfTheWorldCountryCrawler $crawler,
        protected BulkImportProcessor $processor,
        protected ImportBatchDeleteService $deleteService,
    ) {}

    public function index(): View
    {
        $lastBatch = ImportBatch::query()
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        return view('admin.import-ads-of-world.index', [
            'lastBatch' => $lastBatch,
            'defaultCountryUrl' => 'https://www.adsoftheworld.com/countries/iraq',
        ]);
    }

    public function store(ImportAdsOfWorldRequest $request): RedirectResponse
    {
        try {
            $discovered = $this->crawler->discoverCampaignUrls($request->input('country_url'));
            $entries = $discovered['entries'];

            if ($entries === []) {
                return back()
                    ->withInput()
                    ->withErrors(['country_url' => 'No campaign URLs found on that country page.']);
            }

            $batch = ImportBatch::create([
                'user_id' => $request->user()->id,
                'country_url' => $request->input('country_url'),
                'status' => 'queued',
                'total_urls' => count($entries),
                'crawl_max_page' => $discovered['max_page'],
                'queue_order_mode' => 'oldest_first',
            ]);

            $batch->enqueueDiscoveredUrls($entries);
            $batch->update(['total_urls' => $batch->queueItems()->count()]);

            return redirect()
                ->route('admin.import-ads-of-world.show', $batch)
                ->with('success', 'Queued '.$batch->total_urls.' campaigns. Import will start automatically — keep the progress page open.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['country_url' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['country_url' => 'Could not fetch the country page. Please try again.']);
        }
    }

    public function show(ImportBatch $batch): View
    {
        $this->resetStuckItems($batch);

        return view('admin.import-ads-of-world.progress', [
            'batch' => $batch->fresh(),
            'progress' => $this->processor->batchProgress($batch->fresh()),
        ]);
    }

    public function status(ImportBatch $batch): JsonResponse
    {
        $this->resetStuckItems($batch);

        return response()->json($this->processor->batchProgress($batch->fresh()));
    }

    public function process(ImportBatch $batch, Request $request): JsonResponse
    {
        if ($batch->deleted_at) {
            return response()->json(['error' => 'This batch was deleted.'], 410);
        }

        $timeout = (int) config('import.bulk_process_timeout', 120);
        @set_time_limit($timeout);
        @ini_set('max_execution_time', (string) $timeout);

        $this->resetStuckItems($batch);
        $batch = $batch->fresh();

        if ($batch->status === 'paused') {
            return response()->json([
                'ok' => true,
                'paused' => true,
                'progress' => $this->processor->batchProgress($batch),
                'results' => [],
            ]);
        }

        if ($batch->status === 'completed') {
            return response()->json([
                'ok' => true,
                'progress' => $this->processor->batchProgress($batch),
                'results' => [],
            ]);
        }

        try {
            $result = $this->processor->processNext($batch, $request->user());

            return response()->json([
                'ok' => true,
                'progress' => $this->processor->batchProgress($batch->fresh()),
                'results' => [$result],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'progress' => $this->processor->batchProgress($batch->fresh()),
            ], 500);
        }
    }

    public function pause(ImportBatch $batch): JsonResponse
    {
        $this->processor->pause($batch->fresh());

        return response()->json([
            'ok' => true,
            'progress' => $this->processor->batchProgress($batch->fresh()),
        ]);
    }

    public function resume(ImportBatch $batch): JsonResponse
    {
        $this->resetStuckItems($batch);
        $this->processor->resume($batch->fresh());

        return response()->json([
            'ok' => true,
            'progress' => $this->processor->batchProgress($batch->fresh()),
        ]);
    }

    public function retryFailed(ImportBatch $batch): JsonResponse
    {
        $this->resetStuckItems($batch);
        $count = $this->processor->retryFailed($batch->fresh());

        return response()->json([
            'ok' => true,
            'retried' => $count,
            'progress' => $this->processor->batchProgress($batch->fresh()),
        ]);
    }

    public function deleteLast(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        try {
            $this->deleteService->deleteLastBatch($request->user());

            return redirect()
                ->route('admin.import.queue')
                ->with('success', 'Last Iraq import removed successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not delete the last import batch.');
        }
    }

    protected function resetStuckItems(ImportBatch $batch): void
    {
        $minutes = (int) config('import.bulk_stuck_item_minutes', 3);

        $batch->queueItems()
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update(['status' => 'pending']);
    }
}
