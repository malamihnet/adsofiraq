<?php

namespace App\Services\Import;

use App\Exceptions\CampaignImportException;
use App\Models\Campaign;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Log;

class NewIraqCampaignsChecker
{
    public function __construct(
        protected CampaignPageFetcher $fetcher,
        protected CampaignUrlNormalizer $urlNormalizer,
    ) {}

    public function iraqCountryUrl(): string
    {
        return 'https://www.adsoftheworld.com/countries/iraq';
    }

    /**
     * Initialize batch crawl state using page 1 HTML.
     */
    public function initializeBatch(ImportBatch $batch): void
    {
        $baseUrl = $this->iraqCountryUrl();
        $firstHtml = $this->fetchCountryPage($baseUrl);
        $maxPage = $this->detectMaxPage($firstHtml);

        $batch->update([
            'country_url' => $baseUrl,
            'crawl_max_page' => $maxPage,
            'crawl_next_page' => 1,
            'consecutive_existing' => 0,
            'stop_after_existing' => max(1, (int) config('import.new_import_stop_after_existing', 20)),
            'queue_order_mode' => 'newest_first',
        ]);
    }

    /**
     * Crawl exactly one country page (newest pages first) and enqueue NEW campaigns.
     * Stops early if it hits stop_after_existing consecutive existing campaigns.
     *
     * @return array{page: int, discovered: int, enqueued: int, existing: int, stopped: bool}
     */
    public function crawlNextPageAndEnqueue(ImportBatch $batch, int $enqueueLimit = 10): array
    {
        $batch = $batch->fresh();

        $maxPage = (int) ($batch->crawl_max_page ?? 1);
        $nextPage = (int) ($batch->crawl_next_page ?? 1);
        $stopAfter = (int) ($batch->stop_after_existing ?? config('import.new_import_stop_after_existing', 20));

        if ($nextPage > $maxPage) {
            return ['page' => $nextPage, 'discovered' => 0, 'enqueued' => 0, 'existing' => 0, 'stopped' => true];
        }

        $pageUrl = $nextPage === 1 ? $this->iraqCountryUrl() : $this->iraqCountryUrl().'?page='.$nextPage;
        $html = $this->fetchCountryPage($pageUrl);
        $paths = $this->extractCampaignPaths($html);

        $discovered = 0;
        $enqueued = 0;
        $existing = 0;
        $stopped = false;

        $nextSort = (int) ($batch->queueItems()->max('sort_order') ?? -1) + 1;
        $consecutiveExisting = (int) ($batch->consecutive_existing ?? 0);

        foreach ($paths as $path) {
            $discovered++;

            $url = $this->urlNormalizer->normalize('https://www.adsoftheworld.com'.$path);

            // If already queued in this batch, ignore.
            if ($batch->queueItems()->where('url', $url)->exists()) {
                continue;
            }

            $alreadyImported = Campaign::query()->where('source_url', $url)->exists();

            if ($alreadyImported) {
                $existing++;
                $consecutiveExisting++;
                $batch->increment('existing_skipped_count');

                if ($consecutiveExisting >= $stopAfter) {
                    $stopped = true;
                    break;
                }

                continue;
            }

            // Found a new campaign — reset the "existing streak".
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

        $batch->update([
            'crawl_next_page' => $nextPage + 1,
            'consecutive_existing' => $consecutiveExisting,
        ]);

        Log::info('AOTW Iraq incremental crawl: page scanned.', [
            'batch_id' => $batch->id,
            'page' => $nextPage,
            'max_page' => $maxPage,
            'discovered' => $discovered,
            'enqueued' => $enqueued,
            'existing' => $existing,
            'consecutive_existing' => $consecutiveExisting,
            'stopped' => $stopped,
        ]);

        return [
            'page' => $nextPage,
            'discovered' => $discovered,
            'enqueued' => $enqueued,
            'existing' => $existing,
            'stopped' => $stopped,
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

    protected function detectMaxPage(string $html): int
    {
        $max = 1;

        if (preg_match_all('#/countries/[^"\']+\?page=(\d+)#', $html, $matches)) {
            foreach ($matches[1] as $page) {
                $max = max($max, (int) $page);
            }
        }

        if (preg_match('#href=["\']([^"\']+\?page=(\d+))["\'][^>]*>Last#i', $html, $match)) {
            $max = max($max, (int) $match[2]);
        }

        $max = min($max, (int) config('import.max_country_pages', 500));

        return max(1, $max);
    }

    /**
     * @return list<string>
     */
    protected function extractCampaignPaths(string $html): array
    {
        $paths = [];

        if (! preg_match_all('#href=["\'](/campaigns/[^"\']+)#i', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $path) {
            $path = strtok($path, '?') ?: $path;

            if ($path === '/campaigns/new' || str_starts_with($path, '/campaigns/new/')) {
                continue;
            }

            if (preg_match('#^/campaigns/[a-z0-9\-]+#i', $path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}

