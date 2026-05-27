<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetImportedIraqCampaignsCommand extends Command
{
    protected $signature = 'campaigns:reset-imported-iraq
                            {--dry-run : Show counts only (default behaviour)}
                            {--execute : Permanently delete imported campaigns (irreversible)}
                            {--only-batch= : Limit to campaigns from a specific import batch ID}';

    protected $description = 'PREPARATION ONLY: Remove campaigns imported from external URLs (preserves manual submissions)';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $dryRun = ! $execute || (bool) $this->option('dry-run');

        $query = Campaign::query()
            ->where(function ($builder) {
                $builder->whereNotNull('source_url')
                    ->where('source_url', '!=', '');
            });

        if ($this->option('only-batch') !== null) {
            $query->where('source_batch_id', (int) $this->option('only-batch'));
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No imported campaigns matched.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn(sprintf(
                'Dry run: %d imported campaign(s) would be deleted. Manual campaigns (no source_url) are preserved.',
                $count,
            ));
            $this->line('Run with --execute to perform deletion. Bookmarks/watchers cascade per model constraints.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Delete '.$count.' imported campaign(s)? This cannot be undone.')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;

        $query->orderBy('id')->chunkById(50, function ($campaigns) use (&$deleted) {
            foreach ($campaigns as $campaign) {
                $campaign->delete();
                $deleted++;
            }
        });

        Log::warning('Imported Iraq campaigns reset executed', ['deleted' => $deleted]);

        $this->info(sprintf('Deleted %d imported campaign(s).', $deleted));

        return self::SUCCESS;
    }
}
