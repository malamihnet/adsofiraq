<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use Illuminate\Database\Eloquent\Model;

class CampaignTaxonomySyncService
{
    public function __construct(
        protected TaxonomyService $taxonomyService,
    ) {}

    /**
     * @param  array<int, string|int>  $values  Numeric IDs or "new:Name" strings
     * @return array<int, int>
     */
    public function resolveIds(string $type, array $values): array
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

                $model = $this->createByType($type, $name);
                $ids[] = $model->id;

                continue;
            }

            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, string|int>  $agencies
     * @param  array<int, string|int>  $brands
     * @param  array<int, string|int>  $industries
     * @param  array<int, string|int>  $mediumTypes
     * @param  array<int, string|int>  $countries
     */
    public function syncAll(
        Campaign $campaign,
        array $agencies = [],
        array $brands = [],
        array $industries = [],
        array $mediumTypes = [],
        array $countries = [],
    ): void {
        $campaign->agencies()->sync($this->resolveIds('agencies', $agencies));
        $campaign->brands()->sync($this->resolveIds('brands', $brands));
        $campaign->industries()->sync($this->resolveIds('industries', $industries));
        $campaign->mediumTypes()->sync($this->resolveIds('medium_types', $mediumTypes));
        $campaign->countries()->sync($this->resolveIds('countries', $countries));
    }

    /**
     * @return array{id: int, name: string}[]
     */
    public function selectedForForm(Campaign $campaign): array
    {
        return [
            'agencies' => $campaign->agencies->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'brands' => $campaign->brands->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'industries' => $campaign->industries->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'medium_types' => $campaign->mediumTypes->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
            'countries' => $campaign->countries->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function oldInputSelections(): array
    {
        $types = [
            'agencies' => Agency::class,
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

    protected function createByType(string $type, string $name): Model
    {
        return match ($type) {
            'agencies' => $this->taxonomyService->findOrCreateAgency($name),
            'brands' => $this->taxonomyService->findOrCreateBrand($name),
            'industries' => $this->taxonomyService->findOrCreateIndustry($name),
            'medium_types' => $this->taxonomyService->findOrCreateMediumType($name),
            'countries' => $this->taxonomyService->findOrCreateCountry($name),
            default => throw new \InvalidArgumentException("Unknown taxonomy type: {$type}"),
        };
    }
}
