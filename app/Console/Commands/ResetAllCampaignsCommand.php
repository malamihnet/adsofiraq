<?php

namespace App\Console\Commands;

use App\Services\CampaignResetService;
use Illuminate\Console\Command;

class ResetAllCampaignsCommand extends Command
{
    protected $signature = 'campaigns:reset-all
                            {--dry-run : Show counts only}
                            {--execute : Run destructive reset (requires confirmation in non-interactive: --force)}
                            {--force : Skip interactive confirmation with --execute}';

    protected $description = 'DANGER: Delete ALL campaigns, related records, and campaign media files';

    public function handle(CampaignResetService $resetService): int
    {
        $counts = $resetService->gatherCounts();
        $this->table(['Metric', 'Count'], collect($counts)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($this->option('dry-run') || ! $this->option('execute')) {
            $this->warn('Dry run / preview only. Pass --execute --force to delete everything.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('This will DELETE ALL campaigns. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $session = $resetService->startSession(0, dryRun: false);

        while (! ($session['completed'] ?? false)) {
            $result = $resetService->tick($session['id']);
            $session = $resetService->getSession($session['id']) ?? $session;
            $progress = $result['progress'] ?? [];

            $this->line(sprintf(
                'Phase %s — %d%% — campaigns deleted %d — last action %s',
                $progress['phase'] ?? '?',
                $progress['percent'] ?? 0,
                $progress['processed']['campaigns'] ?? 0,
                $progress['last_action'] ?? '—',
            ));

            if (! ($result['ok'] ?? true)) {
                $this->error($result['error'] ?? 'Unknown error');

                return self::FAILURE;
            }
        }

        $this->info('Reset complete.');

        return self::SUCCESS;
    }
}
