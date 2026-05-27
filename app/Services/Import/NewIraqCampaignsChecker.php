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
        protected CampaignListingParser $listingParser,
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
        $maxPage = $this->listingParser->detectMaxPage($firstHtml);

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
        $paths = $this->listingParser->extractCampaignPaths($html);

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

        $action = $stopped ? 'completed_stop_existing' : ($enqueued > 0 ? 'urls_found' : ($discovered > 0 ? 'existing_url' : 'crawling_page'));

        Log::info('AOTW Iraq incremental crawl: page scanned.', [
            'batch_id' => $batch->id,
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

    protected function fetchCountryPage(string $url): string
    {
        return $this->fetcher->fetch(
            $url,
            (int) config('import.country_page_timeout', 15),
            (int) config('import.country_page_retries', 3),
        );
    }

}

