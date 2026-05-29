<?php

namespace App\Services;

use App\Enums\AgencyCampaignRole;
use App\Enums\AgencyCompanyRole;
use App\Models\Agency;
use App\Models\AgencyRole;
use Illuminate\Support\Collection;

class AgencyRoleService
{
    public function ensureRole(Agency $agency, AgencyCompanyRole $role): void
    {
        AgencyRole::firstOrCreate([
            'agency_id' => $agency->id,
            'role' => $role->value,
        ]);

        $this->syncProductionHouseFlag($agency->fresh(['roles']));
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncRoles(Agency $agency, array $roles): void
    {
        $roles = collect($roles)
            ->filter(fn ($role) => in_array($role, AgencyCompanyRole::values(), true))
            ->unique()
            ->values()
            ->all();

        AgencyRole::query()
            ->where('agency_id', $agency->id)
            ->when(
                $roles !== [],
                fn ($query) => $query->whereNotIn('role', $roles),
            )
            ->when(
                $roles === [],
                fn ($query) => $query,
            )
            ->delete();

        foreach ($roles as $role) {
            AgencyRole::firstOrCreate([
                'agency_id' => $agency->id,
                'role' => $role,
            ]);
        }

        $this->syncProductionHouseFlag($agency->fresh(['roles']));
    }

    public function syncProductionHouseFlag(Agency $agency): void
    {
        $hasProductionHouseRole = $agency->hasRole(AgencyCompanyRole::ProductionHouse);

        if ($agency->is_production_house !== $hasProductionHouseRole) {
            $agency->forceFill(['is_production_house' => $hasProductionHouseRole])->saveQuietly();
        }
    }

    public function backfillAll(): int
    {
        $count = 0;

        Agency::query()->with(['roles'])->chunkById(100, function ($agencies) use (&$count) {
            foreach ($agencies as $agency) {
                if ($this->backfillAgency($agency)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    public function backfillAgency(Agency $agency): bool
    {
        $agency->loadMissing('roles');
        $before = $agency->roles->pluck('role')->sort()->values()->all();

        if ($agency->agencyCampaigns()->exists()) {
            AgencyRole::firstOrCreate([
                'agency_id' => $agency->id,
                'role' => AgencyCompanyRole::Agency->value,
            ]);
        }

        if ($agency->is_production_house || $agency->productionHouseCampaigns()->exists()) {
            AgencyRole::firstOrCreate([
                'agency_id' => $agency->id,
                'role' => AgencyCompanyRole::ProductionHouse->value,
            ]);
        }

        $this->syncProductionHouseFlag($agency->fresh(['roles']));

        $after = $agency->fresh(['roles'])->roles->pluck('role')->sort()->values()->all();

        return $before !== $after;
    }

    /**
     * @return Collection<int, AgencyCompanyRole>
     */
    public function resolvedRoles(Agency $agency): Collection
    {
        if ($agency->relationLoaded('roles') && $agency->roles->isNotEmpty()) {
            return $agency->roles
                ->map(fn (AgencyRole $record) => AgencyCompanyRole::from($record->role))
                ->unique(fn (AgencyCompanyRole $role) => $role->value)
                ->values();
        }

        if ($agency->roles()->exists()) {
            return $agency->roles()
                ->pluck('role')
                ->map(fn (string $role) => AgencyCompanyRole::from($role))
                ->unique(fn (AgencyCompanyRole $role) => $role->value)
                ->values();
        }

        $fallback = collect();

        if ($agency->is_production_house) {
            $fallback->push(AgencyCompanyRole::ProductionHouse);
        }

        $fallback->push(AgencyCompanyRole::Agency);

        return $fallback->unique(fn (AgencyCompanyRole $role) => $role->value)->values();
    }

    public function attachImporterRole(Agency $agency, AgencyCompanyRole $role): void
    {
        $this->ensureRole($agency, $role);
    }

    public function roleFromCampaignPivot(AgencyCampaignRole $role): ?AgencyCompanyRole
    {
        return match ($role) {
            AgencyCampaignRole::Agency => AgencyCompanyRole::Agency,
            AgencyCampaignRole::ProductionHouse => AgencyCompanyRole::ProductionHouse,
        };
    }
}
