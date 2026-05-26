<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesCampaignTaxonomies
{
    /**
     * @return array<string, mixed>
     */
    protected function campaignTaxonomyRules(): array
    {
        $rules = [];

        foreach (config('campaign_taxonomy.limits') as $field => $max) {
            $rules[$field] = ['nullable', 'array', "max:{$max}"];
            $rules["{$field}.*"] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function validateCampaignTaxonomyItems(Validator $validator): void
    {
        $tables = [
            'agencies' => 'agencies',
            'brands' => 'brands',
            'industries' => 'industries',
            'medium_types' => 'medium_types',
            'countries' => 'countries',
        ];

        $labels = [
            'agencies' => 'agencies or schools',
            'brands' => 'brands',
            'industries' => 'industries',
            'medium_types' => 'medium types',
            'countries' => 'countries',
        ];

        foreach ($tables as $field => $table) {
            $values = $this->input($field, []);

            if (! is_array($values)) {
                continue;
            }

            $seen = [];

            foreach ($values as $index => $value) {
                $value = trim((string) $value);

                if ($value === '') {
                    continue;
                }

                $key = strtolower($value);

                if (isset($seen[$key])) {
                    $validator->errors()->add("{$field}.{$index}", 'Duplicate entries are not allowed.');

                    continue;
                }

                $seen[$key] = true;

                if (str_starts_with($value, 'new:')) {
                    $name = trim(substr($value, 4));

                    if ($name === '') {
                        $validator->errors()->add("{$field}.{$index}", 'Please enter a name for the new item.');
                    }

                    continue;
                }

                if (! is_numeric($value) || ! \DB::table($table)->where('id', (int) $value)->exists()) {
                    $validator->errors()->add("{$field}.{$index}", 'The selected '.rtrim($labels[$field], 's').' is invalid.');
                }
            }

            $max = config("campaign_taxonomy.limits.{$field}");

            if (count($values) > $max) {
                $validator->errors()->add(
                    $field,
                    "You can only select up to {$max} {$labels[$field]}."
                );
            }
        }
    }
}
