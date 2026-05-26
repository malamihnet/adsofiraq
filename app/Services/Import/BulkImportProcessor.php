<?php

namespace App\Services\Import;

use App\Exceptions\CampaignImportException;
use App\Models\Campaign;
use App\Models\ImportBatch;
use App\Models\ImportQueueItem;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BulkImportProcessor
{
    public function __construct(
        protected CampaignImporterService $importer,
    ) {}

    /**
     * @return array{status: string, item: ?ImportQueueItem, campaign: ?Campaign, message: ?string}
     */
    public function processNext(ImportBatch $batch, User $admin): array
    {
        if (in_array($batch->status, ['paused', 'completed'], true)) {
            return [
                'status' => $batch->status,
                'item' => null,
                'campaign' => null,
                'message' => $batch->status === 'paused' ? 'Batch is paused.' : 'Batch is complete.',
            ];
        }

        if ($batch->status === 'queued') {
            $batch->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
        }

        $item = ImportQueueItem::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'pending')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $item) {
            $this->finalizeBatch($batch);

            return ['status' => 'completed', 'item' => null, 'campaign' => null, 'message' => null];
        }

        $item->update(['status' => 'processing']);

        try {
            $existing = $this->importer->findDuplicate($item->url);

            if ($existing) {
                $item->update([
                    'status' => 'skipped',
                    'error_message' => 'Duplicate source_url',
                    'campaign_id' => $existing->id,
                ]);
                $batch->refreshCounts();

                return [
                    'status' => 'skipped',
                    'item' => $item->fresh(),
                    'campaign' => $existing,
                    'message' => 'Skipped duplicate',
                ];
            }

            $campaign = $this->importer->importBulkCampaign($item->url, $admin, $batch->id);

            $item->update([
                'status' => 'done',
                'campaign_id' => $campaign->id,
                'error_message' => null,
            ]);

            $batch->refreshCounts();

            return [
                'status' => 'done',
                'item' => $item->fresh(),
                'campaign' => $campaign,
                'message' => null,
            ];
        } catch (CampaignImportException $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $batch->refreshCounts();

            Log::warning('Bulk import: campaign failed.', [
                'batch_id' => $batch->id,
                'url' => $item->url,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'item' => $item->fresh(),
                'campaign' => null,
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $batch->refreshCounts();
            report($e);

            return [
                'status' => 'failed',
                'item' => $item->fresh(),
                'campaign' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function processBatch(ImportBatch $batch, User $admin): void
    {
        while (true) {
            $result = $this->processNext($batch, $admin);

            if ($result['status'] === 'completed') {
                break;
            }
        }
    }

    protected function finalizeBatch(ImportBatch $batch): void
    {
        $batch->refreshCounts();
        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function batchProgress(ImportBatch $batch): array
    {
        $batch->refreshCounts();

        $pending = $batch->queueItems()->where('status', 'pending')->count();
        $processing = $batch->queueItems()->where('status', 'processing')->count();
        $done = $batch->imported_count;
        $failed = $batch->failed_count;
        $skipped = $batch->skipped_count;
        $total = $batch->total_urls;
        $processed = $done + $failed + $skipped;

        $nextPending = $batch->queueItems()
            ->where('status', 'pending')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $firstInQueue = $batch->queueItems()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $currentlyProcessing = $batch->queueItems()
            ->where('status', 'processing')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $batchStatus = $batch->status;
        $completed = $batchStatus === 'completed';
        $paused = $batchStatus === 'paused';
        $canProcess = ! $completed && ! $paused && ($pending > 0 || $processing > 0);

        return [
            'batch_id' => $batch->id,
            'status' => $batchStatus,
            'total' => $total,
            'imported' => $done,
            'failed' => $failed,
            'skipped' => $skipped,
            'pending' => $pending,
            'processing' => $processing,
            'processed' => $processed,
            'percent' => $total > 0 ? (int) round(($processed / $total) * 100) : 100,
            'completed' => $completed,
            'paused' => $paused,
            'can_process' => $canProcess,
            'can_auto_process' => $canProcess,
            'queue_order_mode' => $batch->queue_order_mode ?? 'oldest_first',
            'queue_order_label' => 'Oldest first',
            'crawl_max_page' => $batch->crawl_max_page,
            'first_queue_url' => $firstInQueue?->url,
            'next_pending_url' => $nextPending?->url,
            'current_url' => $currentlyProcessing?->url ?? $nextPending?->url,
        ];
    }

    public function pause(ImportBatch $batch): void
    {
        if ($batch->status === 'completed') {
            return;
        }

        $batch->update(['status' => 'paused']);
    }

    public function resume(ImportBatch $batch): void
    {
        if ($batch->status === 'completed') {
            return;
        }

        $hasWork = $batch->queueItems()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if (! $hasWork && $batch->queueItems()->where('status', 'failed')->doesntExist()) {
            return;
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => $batch->started_at ?? now(),
            'completed_at' => null,
        ]);
    }

    public function retryFailed(ImportBatch $batch): int
    {
        $count = $batch->queueItems()
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

        if ($count > 0 && $batch->status !== 'processing') {
            $this->resume($batch);
        }

        return $count;
    }
}
