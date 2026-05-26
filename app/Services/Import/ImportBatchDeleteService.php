<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Models\ImportBatch;
use App\Models\ImportQueueItem;
use App\Models\User;
use App\Services\CampaignUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportBatchDeleteService
{
    public function __construct(
        protected CampaignUploadService $uploadService,
    ) {}

    public function findLastDeletableBatch(): ?ImportBatch
    {
        return ImportBatch::query()
            ->whereNull('deleted_at')
            ->whereHas('campaigns')
            ->orderByDesc('created_at')
            ->first();
    }

    public function deleteLastBatch(User $admin): ImportBatch
    {
        $batch = $this->findLastDeletableBatch();

        if (! $batch) {
            throw new \RuntimeException('No imported batch found to delete.');
        }

        return DB::transaction(function () use ($batch, $admin) {
            $campaigns = Campaign::query()
                ->where('source_batch_id', $batch->id)
                ->get();

            foreach ($campaigns as $campaign) {
                $this->uploadService->deleteCampaignFiles($campaign);
                $campaign->videos()->delete();
                $campaign->assets()->delete();
                $campaign->forceDelete();
            }

            ImportQueueItem::query()->where('batch_id', $batch->id)->delete();

            $batch->update([
                'deleted_at' => now(),
                'deleted_by' => $admin->id,
                'status' => 'deleted',
            ]);

            Log::info('Bulk import batch deleted.', [
                'batch_id' => $batch->id,
                'admin_id' => $admin->id,
                'campaigns_removed' => $campaigns->count(),
            ]);

            return $batch->fresh();
        });
    }
}
