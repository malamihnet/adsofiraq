<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            'Automotive', 'Banking & Finance', 'Consumer Goods', 'Education',
            'Entertainment', 'Food & Beverage', 'Government', 'Healthcare',
            'Real Estate', 'Retail', 'Technology', 'Telecommunications',
            'Travel & Tourism', 'Non-Profit',
        ];

        foreach ($industries as $name) {
            Industry::firstOrCreate(['name' => $name], ['slug' => str($name)->slug()]);
        }
    }
}
