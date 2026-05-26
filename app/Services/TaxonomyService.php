<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxonomyService
{
    public function findOrCreateBrand(?string $name): ?Brand
    {
        return $this->findOrCreateByName(Brand::class, $name);
    }

    public function findOrCreateAgency(?string $name): ?Agency
    {
        return $this->findOrCreateByName(Agency::class, $name);
    }

    public function findOrCreateIndustry(?string $name): ?Industry
    {
        return $this->findOrCreateByName(Industry::class, $name);
    }

    public function findOrCreateMediumType(?string $name): ?MediumType
    {
        return $this->findOrCreateByName(MediumType::class, $name);
    }

    public function findOrCreateCountry(?string $name): ?Country
    {
        return $this->findOrCreateByName(Country::class, $name);
    }

    public function resolveBrand(?int $id, ?string $name): ?Brand
    {
        return $this->resolveModel(Brand::class, $id, $name);
    }

    public function resolveAgency(?int $id, ?string $name): ?Agency
    {
        return $this->resolveModel(Agency::class, $id, $name);
    }

    public function resolveIndustry(?int $id, ?string $name): ?Industry
    {
        return $this->resolveModel(Industry::class, $id, $name);
    }

    public function resolveMediumType(?int $id, ?string $name): ?MediumType
    {
        return $this->resolveModel(MediumType::class, $id, $name);
    }

    public function resolveCountry(?int $id, ?string $name): ?Country
    {
        return $this->resolveModel(Country::class, $id, $name);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function resolveModel(string $modelClass, ?int $id, ?string $name): ?Model
    {
        if ($id) {
            return $modelClass::find($id);
        }

        return $this->findOrCreateByName($modelClass, $name);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function findOrCreateByName(string $modelClass, ?string $name): ?Model
    {
        if (empty(trim($name ?? ''))) {
            return null;
        }

        $name = trim($name);

        return $modelClass::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }
}
