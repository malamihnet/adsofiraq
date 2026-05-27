<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Import\BulkImportProcessor;
use App\Services\Import\NewIraqCampaignsChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CheckNewCampaignsController extends Controller
{
    public function __construct(
        protected NewIraqCampaignsChecker $checker,
        protected BulkImportProcessor $processor,
    ) {}

    public function index(): View
    {
        $lastIncremental = ImportBatch::query()
            ->where('purpose', 'check_new_iraq')
            ->where(function ($q) {
                $q->where('import_mode', NewIraqCampaignsChecker::MODE_INCREMENTAL)
                    ->orWhereNull('import_mode');
            })
            ->orderByDesc('created_at')
            ->first();

        $lastFullRebuild = ImportBatch::query()
            ->where('purpose', 'check_new_iraq')
            ->where('import_mode', NewIraqCampaignsChecker::MODE_FULL_REBUILD)
            ->orderByDesc('created_at')
            ->first();

        return view('admin.check-new-campaigns.index', [
            'lastIncrementalBatch' => $lastIncremental,
            'lastFullRebuildBatch' => $lastFullRebuild,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $batch = $this->createBatch($request, NewIraqCampaignsChecker::MODE_INCREMENTAL);

        return redirect()
            ->route('admin.check-new-campaigns.show', $batch)
            ->with('success', 'Checker started. Keep this page open while it runs.');
    }

    public function startFullRebuild(Request $request): RedirectResponse
    {
        $batch = $this->createBatch($request, NewIraqCampaignsChecker::MODE_FULL_REBUILD);

        return redirect()
            ->route('admin.check-new-campaigns.show', $batch)
            ->with('success', 'Fresh Iraq import started (oldest first). Keep this page open until it completes.');
    }

    public function show(ImportBatch $batch): View
    {
        $this->assertIraqCheckerBatch($batch);
        $this->resetStuckItems($batch);

        return view('admin.check-new-campaigns.progress', [
            'batch' => $batch->fresh(),
            'progress' => $this->statusArray($batch->fresh()),
        ]);
    }

    public function status(ImportBatch $batch): JsonResponse
    {
        $this->assertIraqCheckerBatch($batch);
        $this->resetStuckItems($batch);

        return response()->json($this->statusArray($batch->fresh()));
    }

    public function process(ImportBatch $batch, Request $request): JsonResponse
    {
        $this->assertIraqCheckerBatch($batch);

        $timeout = (int) config('import.bulk_process_timeout', 120);
        @set_time_limit($timeout);
        @ini_set('max_execution_time', (string) $timeout);

        $this->resetStuckItems($batch);
        $batch = $batch->fresh();

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

        $lastAction = null;
        $lastError = null;
        $fullRebuild = $this->checker->isFullRebuild($batch);

        try {
            $hasPending = $batch->queueItems()->where('status', 'pending')->exists();
            $crawlInfo = null;

            if (! $hasPending) {
                $lastAction = 'crawling_page';
                $enqueueLimit = $fullRebuild
                    ? (int) config('import.full_rebuild_enqueue_per_page', 100)
                    : 10;
                $crawlInfo = $this->checker->crawlNextPageAndEnqueue($batch, enqueueLimit: $enqueueLimit);
                $lastAction = (string) ($crawlInfo['action'] ?? 'crawling_page');
            }

            $batch = $batch->fresh();
            $result = null;

            if ($batch->queueItems()->where('status', 'pending')->exists()) {
                $lastAction = 'importing_url';
                $result = $this->processor->processNext($batch, $request->user());
                $batch = $batch->fresh();

                if (($result['status'] ?? null) === 'failed') {
                    $lastAction = 'failed';
                    $lastError = $result['message'] ?? null;
                } elseif (($result['status'] ?? null) === 'skipped') {
                    $lastAction = 'existing_url';
                }
            } else {
                $stopped = (bool) ($crawlInfo['stopped'] ?? false);
                $reachedEnd = $fullRebuild
                    ? (int) ($batch->crawl_next_page ?? 1) < 1
                    : (int) ($batch->crawl_next_page ?? 1) > (int) ($batch->crawl_max_page ?? 1);

                if ($stopped) {
                    $lastAction = 'completed_stop_existing';
                } elseif ($reachedEnd) {
                    $lastAction = 'completed';
                }

                if ($stopped || $reachedEnd) {
                    $batch->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    $batch = $batch->fresh();
                }
            }

            $progress = $this->statusArray($batch);

            Log::info('Check new campaigns process', [
                'batch_id' => $batch->id,
                'import_mode' => $batch->import_mode,
                'status' => $batch->status,
                'current_page' => $progress['crawl_display_page'] ?? null,
                'max_page' => (int) ($batch->crawl_max_page ?? 1),
                'urls_found' => $crawlInfo['urls_found'] ?? null,
                'pending' => $progress['pending'] ?? 0,
                'existing_streak' => $progress['consecutive_existing'] ?? 0,
                'action' => $lastAction,
            ]);

            return response()->json([
                'ok' => true,
                'crawl' => $crawlInfo,
                'progress' => $progress,
                'results' => $result ? [$result] : [],
                'debug' => [
                    'last_action' => $lastAction,
                    'last_error' => $lastError,
                    'http_status' => 200,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            $lastError = $e->getMessage();

            Log::warning('Check new campaigns process failed', [
                'batch_id' => $batch->id,
                'error' => $lastError,
            ]);

            return response()->json([
                'ok' => false,
                'error' => $lastError,
                'progress' => $this->statusArray($batch->fresh()),
                'debug' => [
                    'last_action' => 'failed',
                    'last_error' => $lastError,
                    'http_status' => 500,
                ],
            ], 500);
        }
    }

    public function pause(ImportBatch $batch): JsonResponse
    {
        $this->assertIraqCheckerBatch($batch);
        $batch->update(['status' => 'paused']);

        return response()->json(['ok' => true, 'progress' => $this->statusArray($batch->fresh())]);
    }

    public function resume(ImportBatch $batch): JsonResponse
    {
        $this->assertIraqCheckerBatch($batch);

        if ($batch->status !== 'completed') {
            $batch->update([
                'status' => 'processing',
                'started_at' => $batch->started_at ?? now(),
                'completed_at' => null,
            ]);
        }

        return response()->json(['ok' => true, 'progress' => $this->statusArray($batch->fresh())]);
    }

    public function retryFailed(ImportBatch $batch): JsonResponse
    {
        $this->assertIraqCheckerBatch($batch);

        $this->resetStuckItems($batch);
        $count = $this->processor->retryFailed($batch->fresh());

        return response()->json([
            'ok' => true,
            'retried' => $count,
            'progress' => $this->statusArray($batch->fresh()),
        ]);
    }

    protected function createBatch(Request $request, string $importMode): ImportBatch
    {
        $fullRebuild = $importMode === NewIraqCampaignsChecker::MODE_FULL_REBUILD;

        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'country_url' => $this->checker->iraqCountryUrl(),
            'purpose' => 'check_new_iraq',
            'import_mode' => $importMode,
            'status' => 'queued',
            'total_urls' => 0,
            'queue_order_mode' => $fullRebuild ? 'oldest_first' : 'newest_first',
            'existing_skipped_count' => 0,
            'stop_after_existing' => $fullRebuild
                ? 0
                : max(1, (int) config('import.new_import_stop_after_existing', 20)),
        ]);

        $this->checker->initializeBatch($batch);

        return $batch->fresh();
    }

    protected function assertIraqCheckerBatch(ImportBatch $batch): void
    {
        if ($batch->purpose !== 'check_new_iraq') {
            abort(404);
        }
    }

    protected function statusArray(ImportBatch $batch): array
    {
        $progress = $this->processor->batchProgress($batch);
        $fullRebuild = $this->checker->isFullRebuild($batch);

        $progress['purpose'] = $batch->purpose;
        $progress['import_mode'] = $batch->import_mode ?? NewIraqCampaignsChecker::MODE_INCREMENTAL;
        $progress['existing_skipped'] = (int) ($batch->existing_skipped_count ?? 0);
        $progress['crawl_next_page'] = (int) ($batch->crawl_next_page ?? 1);
        $progress['crawl_max_page'] = (int) ($batch->crawl_max_page ?? 1);
        $progress['stop_after_existing'] = (int) ($batch->stop_after_existing ?? config('import.new_import_stop_after_existing', 20));
        $progress['consecutive_existing'] = (int) ($batch->consecutive_existing ?? 0);
        $progress['crawl_display_page'] = $this->crawlDisplayPage($batch);
        $progress['queue_order_label'] = $fullRebuild
            ? 'Oldest pages first (full rebuild)'
            : 'Newest pages first (incremental)';

        $pending = (int) ($progress['pending'] ?? 0);
        $processing = (int) ($progress['processing'] ?? 0);
        $needsCrawl = $fullRebuild
            ? $progress['crawl_next_page'] >= 1
            : $progress['crawl_next_page'] <= $progress['crawl_max_page'];
        $isActive = in_array($batch->status, ['queued', 'processing'], true);

        if (! $progress['completed'] && (int) ($progress['total'] ?? 0) === 0) {
            $progress['percent'] = 0;
            $progress['phase'] = 'preparing';
        } else {
            $progress['phase'] = $progress['completed'] ? 'completed' : 'processing';
        }

        $progress['can_auto_process'] = ! $progress['completed']
            && ! $progress['paused']
            && ($pending > 0 || $processing > 0 || ($needsCrawl && $isActive));

        $progress['can_process'] = $progress['can_auto_process'];

        return $progress;
    }

    protected function crawlDisplayPage(ImportBatch $batch): int
    {
        $next = (int) ($batch->crawl_next_page ?? 1);
        $max = (int) ($batch->crawl_max_page ?? 1);

        if ($this->checker->isFullRebuild($batch)) {
            if ($next < 1) {
                return 0;
            }

            return min($next, $max);
        }

        return max(0, $next - 1);
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
