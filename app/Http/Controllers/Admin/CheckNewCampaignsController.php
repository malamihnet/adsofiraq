<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Import\BulkImportProcessor;
use App\Services\Import\NewIraqCampaignsChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckNewCampaignsController extends Controller
{
    public function __construct(
        protected NewIraqCampaignsChecker $checker,
        protected BulkImportProcessor $processor,
    ) {}

    public function index(): View
    {
        $last = ImportBatch::query()
            ->where('purpose', 'check_new_iraq')
            ->orderByDesc('created_at')
            ->first();

        return view('admin.check-new-campaigns.index', [
            'lastBatch' => $last,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'country_url' => $this->checker->iraqCountryUrl(),
            'purpose' => 'check_new_iraq',
            'status' => 'queued',
            'total_urls' => 0,
            'queue_order_mode' => 'newest_first',
            'existing_skipped_count' => 0,
            'stop_after_existing' => max(1, (int) config('import.new_import_stop_after_existing', 20)),
        ]);

        $this->checker->initializeBatch($batch);

        return redirect()
            ->route('admin.check-new-campaigns.show', $batch)
            ->with('success', 'Checker started. Keep this page open while it runs.');
    }

    public function show(ImportBatch $batch): View
    {
        $this->resetStuckItems($batch);

        return view('admin.check-new-campaigns.progress', [
            'batch' => $batch->fresh(),
            'progress' => $this->statusArray($batch->fresh()),
        ]);
    }

    public function status(ImportBatch $batch): JsonResponse
    {
        $this->resetStuckItems($batch);

        return response()->json($this->statusArray($batch->fresh()));
    }

    public function process(ImportBatch $batch, Request $request): JsonResponse
    {
        $timeout = (int) config('import.bulk_process_timeout', 120);
        @set_time_limit($timeout);
        @ini_set('max_execution_time', (string) $timeout);

        $this->resetStuckItems($batch);
        $batch = $batch->fresh();

        if ($batch->purpose !== 'check_new_iraq') {
            return response()->json(['ok' => false, 'error' => 'Invalid batch purpose.'], 400);
        }

        if ($batch->status === 'queued') {
            $batch->update([
                'status' => 'processing',
                'started_at' => $batch->started_at ?? now(),
            ]);
            $batch = $batch->fresh();
        }

        if ($batch->status === 'paused') {
            return response()->json(['ok' => true, 'progress' => $this->statusArray($batch), 'results' => []]);
        }

        if ($batch->status === 'completed') {
            return response()->json(['ok' => true, 'progress' => $this->statusArray($batch), 'results' => []]);
        }

        try {
            // If nothing is pending, crawl the next page to find new campaigns (fast) and enqueue them.
            $hasPending = $batch->queueItems()->where('status', 'pending')->exists();
            $crawlInfo = null;

            if (! $hasPending) {
                $crawlInfo = $this->checker->crawlNextPageAndEnqueue($batch, enqueueLimit: 10);
            }

            // If we now have pending items, process exactly one campaign.
            $batch = $batch->fresh();
            $result = null;

            if ($batch->queueItems()->where('status', 'pending')->exists()) {
                $result = $this->processor->processNext($batch, $request->user());
            } else {
                // No work left: either we reached the end or hit the stop-after-existing threshold.
                $progress = $this->processor->batchProgress($batch);
                $stopped = (bool) ($crawlInfo['stopped'] ?? false);
                $reachedEnd = (int) ($batch->crawl_next_page ?? 1) > (int) ($batch->crawl_max_page ?? 1);

                if ($stopped || $reachedEnd) {
                    $batch->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }
            }

            return response()->json([
                'ok' => true,
                'crawl' => $crawlInfo,
                'progress' => $this->statusArray($batch->fresh()),
                'results' => $result ? [$result] : [],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'progress' => $this->statusArray($batch->fresh()),
            ], 500);
        }
    }

    public function pause(ImportBatch $batch): JsonResponse
    {
        if ($batch->purpose !== 'check_new_iraq') {
            return response()->json(['ok' => false, 'error' => 'Invalid batch purpose.'], 400);
        }

        $batch->update(['status' => 'paused']);

        return response()->json(['ok' => true, 'progress' => $this->statusArray($batch->fresh())]);
    }

    public function resume(ImportBatch $batch): JsonResponse
    {
        if ($batch->purpose !== 'check_new_iraq') {
            return response()->json(['ok' => false, 'error' => 'Invalid batch purpose.'], 400);
        }

        if ($batch->status !== 'completed') {
            $batch->update([
                'status' => 'processing',
                'started_at' => $batch->started_at ?? now(),
                'completed_at' => null,
            ]);
        }

        return response()->json(['ok' => true, 'progress' => $this->statusArray($batch->fresh())]);
    }

    protected function statusArray(ImportBatch $batch): array
    {
        $progress = $this->processor->batchProgress($batch);

        $progress['purpose'] = $batch->purpose;
        $progress['existing_skipped'] = (int) ($batch->existing_skipped_count ?? 0);
        $progress['crawl_next_page'] = (int) ($batch->crawl_next_page ?? 1);
        $progress['crawl_max_page'] = (int) ($batch->crawl_max_page ?? 1);
        $progress['stop_after_existing'] = (int) ($batch->stop_after_existing ?? config('import.new_import_stop_after_existing', 20));
        $progress['consecutive_existing'] = (int) ($batch->consecutive_existing ?? 0);

        // UI labels for this feature
        $progress['queue_order_label'] = 'Newest pages first';

        return $progress;
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

