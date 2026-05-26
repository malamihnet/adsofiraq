<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Import\AdsOfTheWorldCountryCrawler;
use App\Services\Import\BulkImportProcessor;
use Illuminate\Console\Command;

class ImportAdsOfWorldCommand extends Command
{
    protected $signature = 'import:ads-of-world
                            {batch? : Existing batch UUID to process}
                            {--url= : Country page URL to crawl and queue (creates new batch)}
                            {--user=1 : Admin user ID to attribute imports}';

    protected $description = 'Process Ads of the World bulk import queue (Iraq country page)';

    public function handle(
        AdsOfTheWorldCountryCrawler $crawler,
        BulkImportProcessor $processor,
    ): int {
        $admin = User::query()->find((int) $this->option('user'));

        if (! $admin || ! $admin->isAdmin()) {
            $this->error('A valid admin user ID is required via --user=');

            return self::FAILURE;
        }

        $batch = $this->resolveBatch($crawler, $admin);

        if (! $batch) {
            return self::FAILURE;
        }

        $this->resetStuckItems($batch);

        @set_time_limit(0);

        $this->info("Processing batch {$batch->id} ({$batch->total_urls} URLs)...");

        $progress = $processor->batchProgress($batch->fresh());
        $bar = $this->output->createProgressBar($batch->total_urls);
        $bar->setProgress($progress['processed']);
        $bar->start();

        while (true) {
            $result = $processor->processNext($batch->fresh(), $admin);
            $batch = $batch->fresh();
            $progress = $processor->batchProgress($batch);
            $bar->setProgress($progress['processed']);

            if ($result['status'] === 'completed') {
                $bar->finish();
                $this->newLine(2);
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Imported', $batch->imported_count],
                        ['Failed', $batch->failed_count],
                        ['Skipped (duplicates)', $batch->skipped_count],
                        ['Total', $batch->total_urls],
                    ]
                );

                return self::SUCCESS;
            }

            if ($result['status'] === 'done' && $result['item']?->url) {
                $this->line('  ✓ '.$result['item']->url);
            } elseif ($result['status'] === 'skipped' && $result['item']?->url) {
                $this->line('  ↷ '.$result['item']->url);
            } elseif ($result['status'] === 'failed' && $result['item']?->url) {
                $this->warn('  ✗ '.$result['item']->url.($result['message'] ? ' — '.$result['message'] : ''));
            }
        }
    }

    protected function resetStuckItems(ImportBatch $batch): void
    {
        $batch->queueItems()
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(2))
            ->update(['status' => 'pending']);
    }

    protected function resolveBatch(AdsOfTheWorldCountryCrawler $crawler, User $admin): ?ImportBatch
    {
        $batchId = $this->argument('batch');

        if ($batchId) {
            $batch = ImportBatch::query()->find($batchId);

            if (! $batch) {
                $this->error("Batch not found: {$batchId}");

                return null;
            }

            return $batch;
        }

        $url = $this->option('url') ?: 'https://www.adsoftheworld.com/countries/iraq';

        $this->info("Crawling {$url}...");

        try {
            $discovered = $crawler->discoverCampaignUrls($url);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return null;
        }

        $entries = $discovered['entries'];

        if ($entries === []) {
            $this->error('No campaigns found.');

            return null;
        }

        $batch = ImportBatch::create([
            'user_id' => $admin->id,
            'country_url' => $url,
            'status' => 'queued',
            'total_urls' => count($entries),
            'crawl_max_page' => $discovered['max_page'],
            'queue_order_mode' => 'oldest_first',
        ]);

        $batch->enqueueDiscoveredUrls($entries);
        $batch->update(['total_urls' => $batch->queueItems()->count()]);

        $this->info('Queued '.$batch->total_urls.' campaigns (oldest first, '.$discovered['max_page'].' pages).');

        return $batch;
    }
}
