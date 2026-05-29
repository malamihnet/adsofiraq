<?php

namespace App\Console\Commands;

use App\Services\AgencyRoleService;
use Illuminate\Console\Command;

class BackfillAgencyRolesCommand extends Command
{
    protected $signature = 'authority:backfill-agency-roles';

    protected $description = 'Backfill agency_roles from campaign pivots and legacy is_production_house flags';

    public function handle(AgencyRoleService $agencyRoles): int
    {
        $updated = $agencyRoles->backfillAll();

        $this->info("Agency roles backfilled. {$updated} agenc".($updated === 1 ? 'y' : 'ies').' updated.');

        return self::SUCCESS;
    }
}
