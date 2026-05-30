<?php

namespace App\Console\Commands;

use App\Services\ProductionHousePivotBackfillService;
use Illuminate\Console\Command;

class BackfillProductionHousePivotsCommand extends Command
{
    protected $signature = 'authority:backfill-production-house-pivots {--dry-run : Report rows without writing}';

    protected $description = 'Mirror legacy agency pivot rows as production_house pivots for ranked production houses';

    public function handle(ProductionHousePivotBackfillService $backfill): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no pivot rows will be created.');
        }

        $created = $backfill->backfill($dryRun);

        $this->info($dryRun
            ? "Would create {$created} production_house pivot row(s)."
            : "Created {$created} production_house pivot row(s).");

        $this->line('Rankings also count legacy agency pivots without this backfill.');

        return self::SUCCESS;
    }
}
