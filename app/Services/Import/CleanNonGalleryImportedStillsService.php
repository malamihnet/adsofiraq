<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CleanNonGalleryImportedStillsService
{
    public function __construct(
        protected CampaignPageFetcher $fetcher,
        protected CampaignPageParser $parser,
        protected CampaignMediaDeduplicationService $mediaDedup,
    ) {}

    /**
     * @return array{
     *     dry_run: bool,
     *     campaign_id: int,
     *     skipped: bool,
     *     message: ?string,
     *     gallery_urls: int,
     *     stills_removed: int,
     *     files_deleted: int,
     * }
     */
    public function cleanCampaign(Campaign $campaign, bool $dryRun = false, bool $deleteFiles = true): array
    {
        $result = [
            'dry_run' => $dryRun,
            'campaign_id' => $campaign->id,
            'skipped' => false,
            'message' => null,
            'gallery_urls' => 0,
            'stills_removed' => 0,
            'files_deleted' => 0,
        ];

        if (empty($campaign->source_url)) {
            $result['skipped'] = true;
            $result['message'] = 'Campaign has no source_url.';

            return $result;
        }

        try {
            $html = $this->fetcher->fetch(
                $campaign->source_url,
                null,
                (int) config('import.download_retries', 3),
            );
            $parsed = $this->parser->parse($html, $campaign->source_url);
        } catch (\Throwable $e) {
            $result['skipped'] = true;
            $result['message'] = $e->getMessage();

            Log::warning('Clean non-gallery stills: fetch/parse failed.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }

        $galleryUrls = is_array($parsed['image_urls'] ?? null) ? $parsed['image_urls'] : [];
        $excludedUrls = is_array($parsed['excluded_still_urls'] ?? null) ? $parsed['excluded_still_urls'] : [];

        $result['gallery_urls'] = count($galleryUrls);

        $removal = $this->mediaDedup->removeNonGalleryStills(
            $campaign->fresh(['assets']),
            $galleryUrls,
            $excludedUrls,
            $dryRun,
            $deleteFiles,
        );

        $result['stills_removed'] = $removal['removed'];
        $result['files_deleted'] = $removal['files_deleted'];

        if ($result['stills_removed'] > 0) {
            Log::info('Clean non-gallery stills: campaign cleaned.', $result);
        }

        return $result;
    }

    /**
     * @return array{
     *     campaigns_processed: int,
     *     campaigns_affected: int,
     *     stills_removed: int,
     *     files_deleted: int,
     *     skipped: int,
     *     dry_run: bool,
     * }
     */
    public function cleanAll(
        bool $dryRun = false,
        bool $deleteFiles = true,
        ?int $limit = null,
        ?int $campaignId = null,
    ): array {
        $stats = [
            'campaigns_processed' => 0,
            'campaigns_affected' => 0,
            'stills_removed' => 0,
            'files_deleted' => 0,
            'skipped' => 0,
            'dry_run' => $dryRun,
        ];

        $query = Campaign::query()
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->orderBy('id');

        if ($campaignId !== null) {
            $query->whereKey($campaignId);
        } elseif ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(25, function (Collection $campaigns) use (&$stats, $dryRun, $deleteFiles) {
            foreach ($campaigns as $campaign) {
                $stats['campaigns_processed']++;
                $result = $this->cleanCampaign($campaign, $dryRun, $deleteFiles);

                if ($result['skipped']) {
                    $stats['skipped']++;

                    continue;
                }

                if ($result['stills_removed'] > 0) {
                    $stats['campaigns_affected']++;
                }

                $stats['stills_removed'] += $result['stills_removed'];
                $stats['files_deleted'] += $result['files_deleted'];
            }
        });

        Log::info('Clean non-gallery stills bulk summary', $stats);

        return $stats;
    }
}
