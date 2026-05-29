<?php

namespace App\Services\Import;

use App\Enums\AgencyCompanyRole;
use App\Services\TaxonomyService;

class CampaignCreditsTaxonomyParser
{
    /** @var list<string> */
    protected array $productionLabels = [
        'production house',
        'production',
        'prod. company',
        'film production',
        'production company',
    ];

    /** @var list<string> */
    protected array $postProductionLabels = [
        'post production',
        'post-production',
        'post house',
        'post prod',
        'editing',
        'color grading',
        'color',
        'vfx studio',
        'vfx',
        'visual effects',
    ];

    /**
     * @return array{
     *     agencies: list<string>,
     *     production_houses: list<string>,
     *     post_production_houses: list<string>
     * }
     */
    public function parse(?string $credits): array
    {
        $agencies = [];
        $productionHouses = [];
        $postProductionHouses = [];

        if ($credits === null || trim($credits) === '') {
            return [
                'agencies' => [],
                'production_houses' => [],
                'post_production_houses' => [],
            ];
        }

        foreach (preg_split('/\r\n|\r|\n/', $credits) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            if (! preg_match('/^(.+?):\s*(.+)$/u', $line, $matches)) {
                continue;
            }

            $label = strtolower(trim($matches[1]));
            $value = trim($matches[2]);

            if ($value === '') {
                continue;
            }

            foreach ($this->splitValues($value) as $name) {
                if ($this->isPostProductionLabel($label)) {
                    $postProductionHouses[] = $name;
                } elseif ($this->isProductionLabel($label)) {
                    $productionHouses[] = $name;
                } elseif ($this->isAgencyLabel($label)) {
                    $agencies[] = $name;
                }
            }
        }

        return [
            'agencies' => array_values(array_unique($agencies)),
            'production_houses' => array_values(array_unique($productionHouses)),
            'post_production_houses' => array_values(array_unique($postProductionHouses)),
        ];
    }

    protected function isProductionLabel(string $label): bool
    {
        foreach ($this->productionLabels as $productionLabel) {
            if ($label === $productionLabel) {
                return true;
            }

            if (strlen($productionLabel) > 12 && str_starts_with($label, $productionLabel)) {
                return true;
            }
        }

        return false;
    }

    protected function isPostProductionLabel(string $label): bool
    {
        foreach ($this->postProductionLabels as $postProductionLabel) {
            if ($label === $postProductionLabel) {
                return true;
            }

            if (str_contains($label, $postProductionLabel)) {
                return true;
            }
        }

        return false;
    }

    protected function isAgencyLabel(string $label): bool
    {
        return in_array($label, [
            'agency',
            'advertising agency',
            'ad agency',
            'creative agency',
        ], true);
    }

    /**
     * @return list<string>
     */
    protected function splitValues(string $value): array
    {
        $parts = preg_split('/\s*,\s*|\s*\/\s*|\s+&\s+/u', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }
}
