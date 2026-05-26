<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\VideoThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue-ready job for async video thumbnail fetching.
 *
 * Uses the database queue driver (cPanel-friendly, no Redis required).
 * Dispatch after campaign creation when background processing is preferred:
 *   FetchCampaignVideoThumbnail::dispatch($campaign);
 */
class FetchCampaignVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Campaign $campaign, public bool $manualUploadProvided = false) {}

    public function handle(VideoThumbnailService $thumbnailService): void
    {
        $this->campaign->refresh();

        $thumbnailService->applyFallbackIfNeeded($this->campaign, $this->manualUploadProvided);
    }
}
