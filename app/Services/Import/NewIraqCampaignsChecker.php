<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Log;

class NewIraqCampaignsChecker
{
    public const MODE_INCREMENTAL = 'incremental';

    public const MODE_FULL_REBUILD = 'full_rebuild';

    public function __construct(
        protected CampaignPageFetcher $fetcher,
        protected CampaignUrlNormalizer $urlNormalizer,
        protected CampaignListingParser $listingParser,
    ) {}

    public function iraqCountryUrl(): string
    {
        return 'https://www.adsoftheworld.com/countries/iraq';
    }

    public function isFullRebuild(ImportBatch $batch): bool
    {
        return ($batch->import_mode ?? self::MODE_INCREMENTAL) === self::MODE_FULL_REBUILD;
    }

    /**
     * Initialize batch crawl state using page 1 HTML.
     */
    public function initializeBatch(ImportBatch $batch): void
    {
        $baseUrl = $this->iraqCountryUrl();
        $firstHtml = $this->fetchCountryPage($baseUrl);
        $maxPage = $this->listingParser->detectMaxPage($firstHtml);
        $fullRebuild = $this->isFullRebuild($batch);

        $batch->update([
            'country_url' => $baseUrl,
            'crawl_max_page' => $maxPage,
            'crawl_next_page' => $fullRebuild ? $maxPage : 1,
            'consecutive_existing' => 0,
            'stop_after_existing' => $fullRebuild
                ? 0
                : max(1, (int) config('import.new_import_stop_after_existing', 20)),
            'queue_order_mode' => $fullRebuild ? 'oldest_first' : 'newest_first',
        ]);
    }

    /**
     * Crawl one country page and enqueue campaigns not yet imported.
     *
     * Incremental: pages 1 → max (newest first), stop after N consecutive existing.
     * Full rebuild: pages max → 1 (oldest first), reverse card order per page, no early stop.
     *
     * @return array{page: int, page_url: string, urls_found: int, discovered: int, enqueued: int, existing: int, stopped: bool, action: string}
     */
    public function crawlNextPageAndEnqueue(ImportBatch $batch, int $enqueueLimit = 10): array
    {
        $batch = $batch->fresh();
        $fullRebuild = $this->isFullRebuild($batch);

        $maxPage = (int) ($batch->crawl_max_page ?? 1);
        $nextPage = (int) ($batch->crawl_next_page ?? 1);
        $stopAfter = (int) ($batch->stop_after_existing ?? config('import.new_import_stop_after_existing', 20));
        $stopOnExistingStreak = ! $fullRebuild && $stopAfter > 0;

        if ($fullRebuild) {
            if ($nextPage < 1) {
                return $this->emptyCrawlResult($nextPage, $maxPage, stopped: true);
            }
        } elseif ($nextPage > $maxPage) {
            return $this->emptyCrawlResult($nextPage, $maxPage, stopped: true);
        }

        $pageUrl = $nextPage === 1 ? $this->iraqCountryUrl() : $this->iraqCountryUrl().'?page='.$nextPage;
        $html = $this->fetchCountryPage($pageUrl);
        $paths = $this->listingParser->extractCampaignPaths($html);

        if ($fullRebuild) {
            $paths = array_reverse($paths);
        }

        $discovered = 0;
        $enqueued = 0;
        $existing = 0;
        $stopped = false;

        $nextSort = (int) ($batch->queueItems()->max('sort_order') ?? -1) + 1;
        $consecutiveExisting = (int) ($batch->consecutive_existing ?? 0);

        foreach ($paths as $path) {
            $discovered++;

            $url = $this->urlNormalizer->normalize('https://www.adsoftheworld.com'.$path);

            if ($batch->queueItems()->where('url', $url)->exists()) {
                continue;
            }

            $alreadyImported = Campaign::query()->where('source_url', $url)->exists();

            if ($alreadyImported) {
                $existing++;
                $consecutiveExisting++;
                $batch->increment('existing_skipped_count');

                if ($stopOnExistingStreak && $consecutiveExisting >= $stopAfter) {
                    $stopped = true;
                    break;
                }

                continue;
            }

            $consecutiveExisting = 0;

            $batch->queueItems()->create([
                'url' => $url,
                'status' => 'pending',
                'page_number' => $nextPage,
                'sort_order' => $nextSort++,
            ]);
            $enqueued++;
            $batch->increment('total_urls');

            if ($enqueued >= max(1, $enqueueLimit)) {
                break;
            }
        }

        $followingPage = $fullRebuild ? $nextPage - 1 : $nextPage + 1;
        $brokeForEnqueueLimit = $enqueued >= max(1, $enqueueLimit);

        $batch->update([
            'crawl_next_page' => $brokeForEnqueueLimit ? $nextPage : $followingPage,
            'consecutive_existing' => $consecutiveExisting,
        ]);

        $action = $stopped ? 'completed_stop_existing' : ($enqueued > 0 ? 'urls_found' : ($discovered > 0 ? 'existing_url' : 'crawling_page'));

        Log::info('AOTW Iraq crawl: page scanned.', [
            'batch_id' => $batch->id,
            'import_mode' => $batch->import_mode,
            'page' => $nextPage,
            'page_url' => $pageUrl,
            'max_page' => $maxPage,
            'urls_found' => count($paths),
            'discovered' => $discovered,
            'enqueued' => $enqueued,
            'existing' => $existing,
            'pending' => $batch->queueItems()->where('status', 'pending')->count(),
            'consecutive_existing' => $consecutiveExisting,
            'stopped' => $stopped,
            'action' => $action,
        ]);

        return [
            'page' => $nextPage,
            'page_url' => $pageUrl,
            'urls_found' => count($paths),
            'discovered' => $discovered,
            'enqueued' => $enqueued,
            'existing' => $existing,
            'stopped' => $stopped,
            'action' => $action,
        ];
    }

    /**
     * @return array{page: int, page_url: string, urls_found: int, discovered: int, enqueued: int, existing: int, stopped: bool, action: string}
     */
    protected function emptyCrawlResult(int $page, int $maxPage, bool $stopped): array
    {
        return [
            'page' => $page,
            'page_url' => '',
            'urls_found' => 0,
            'discovered' => 0,
            'enqueued' => 0,
            'existing' => 0,
            'stopped' => $stopped,
            'action' => 'completed',
        ];
    }

    protected function fetchCountryPage(string $url): string
    {
        return $this->fetcher->fetch(
            $url,
            (int) config('import.country_page_timeout', 15),
            (int) config('import.country_page_retries', 3),
        );
    }
}
