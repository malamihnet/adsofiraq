<?php

namespace App\Services;

use App\Enums\AgencyCompanyRole;
use App\Enums\AgencyCampaignRole;
use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CampaignTaxonomySyncService
{
    public function __construct(
        protected TaxonomyService $taxonomyService,
        protected AgencyRoleService $agencyRoles,
    ) {}

    /**
     * @param  array<int, string|int>  $values  Numeric IDs or "new:Name" strings
     * @return array<int, int>
     */
    public function resolveIds(string $type, array $values, bool $asProductionHouse = false): array
    {
        $ids = [];

        foreach ($values as $value) {
            $value = is_string($value) ? trim($value) : $value;

            if ($value === '' || $value === null) {
                continue;
            }

            if (is_string($value) && str_starts_with($value, 'new:')) {
                $name = trim(substr($value, 4));

                if ($name === '') {
                    continue;
                }

                $model = $this->createByType($type, $name, $asProductionHouse);
                $ids[] = $model->id;

                continue;
            }

            if (is_numeric($value)) {
                $id = (int) $value;

                if ($asProductionHouse) {
                    $agency = Agency::find($id);

                    if ($agency) {
                        $this->agencyRoles->ensureRole($agency, AgencyCompanyRole::ProductionHouse);
                    }
                }

                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, string|int>  $agencies
     * @param  array<int, string|int>  $productionHouses
     * @param  array<int, string|int>  $brands
     * @param  array<int, string|int>  $industries
     * @param  array<int, string|int>  $mediumTypes
     * @param  array<int, string|int>  $countries
     */
    public function syncAll(
        Campaign $campaign,
        array $agencies = [],
        array $productionHouses = [],
        array $brands = [],
        array $industries = [],
        array $mediumTypes = [],
        array $countries = [],
    ): void {
        $this->syncAgencyRole(
            $campaign,
            AgencyCampaignRole::Agency,
            $this->resolveIds('agencies', $agencies),
        );

        $this->syncAgencyRole(
            $campaign,
            AgencyCampaignRole::ProductionHouse,
            $this->resolveIds('agencies', $productionHouses, asProductionHouse: true),
        );

        $campaign->brands()->sync($this->resolveIds('brands', $brands));
        $campaign->industries()->sync($this->resolveIds('industries', $industries));
        $campaign->mediumTypes()->sync($this->resolveIds('medium_types', $mediumTypes));
        $campaign->countries()->sync($this->resolveIds('countries', $countries));
    }

    /**
     * @param  list<int>  $agencyIds
     */
    protected function syncAgencyRole(Campaign $campaign, AgencyCampaignRole $role, array $agencyIds): void
    {
        $now = now();

        DB::table('agency_campaign')
            ->where('campaign_id', $campaign->id)
            ->where('role', $role->value)
            ->when(
                $agencyIds !== [],
                fn ($query) => $query->whereNotIn('agency_id', $agencyIds),
            )
            ->delete();

        foreach ($agencyIds as $agencyId) {
            DB::table('agency_campaign')->updateOrInsert(
                [
                    'campaign_id' => $campaign->id,
                    'agency_id' => $agencyId,
                    'role' => $role->value,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    /**
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public function selectedForForm(Campaign $campaign): array
    {
        $campaign->loadMissing(['agencies', 'productionHouses', 'brands', 'industries', 'mediumTypes', 'countries']);

        return [
            'agencies' => $campaign->agencies->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'production_houses' => $campaign->productionHouses->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'brands' => $campaign->brands->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'industries' => $campaign->industries->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'medium_types' => $campaign->mediumTypes->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'countries' => $campaign->countries->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
        ];
    }

    /**
     * @return array<string, array<int, array{id: int|null, name: string}>>
     */
    public function oldInputSelections(): array
    {
        $types = [
            'agencies' => Agency::class,
            'production_houses' => Agency::class,
            'brands' => Brand::class,
            'industries' => Industry::class,
            'medium_types' => MediumType::class,
            'countries' => Country::class,
        ];

        $result = [];

        foreach ($types as $field => $modelClass) {
            $result[$field] = collect(old($field, []))
                ->filter()
                ->map(function ($value) use ($modelClass) {
                    if (is_string($value) && str_starts_with($value, 'new:')) {
                        return ['id' => null, 'name' => trim(substr($value, 4))];
                    }

                    if (is_numeric($value)) {
                        $model = $modelClass::find((int) $value);

                        return ['id' => (int) $value, 'name' => $model?->name ?? ''];
                    }

                    return null;
                })
                ->filter()
                ->values()
                ->all();
        }

        return $result;
    }

    protected function createByType(string $type, string $name, bool $asProductionHouse = false): Model
    {
        return match ($type) {
            'agencies' => $asProductionHouse
                ? $this->taxonomyService->findOrCreateProductionHouse($name)
                : $this->taxonomyService->findOrCreateAgency($name),
            'brands' => $this->taxonomyService->findOrCreateBrand($name),
            'industries' => $this->taxonomyService->findOrCreateIndustry($name),
            'medium_types' => $this->taxonomyService->findOrCreateMediumType($name),
            'countries' => $this->taxonomyService->findOrCreateCountry($name),
            default => throw new \InvalidArgumentException("Unknown taxonomy type: {$type}"),
        };
    }
}
