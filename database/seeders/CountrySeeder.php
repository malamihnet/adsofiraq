<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'Iraq', 'Kurdistan Region', 'United Arab Emirates', 'Saudi Arabia',
            'Kuwait', 'Jordan', 'Lebanon', 'Egypt', 'Qatar', 'Bahrain', 'Oman',
        ];

        foreach ($countries as $name) {
            Country::firstOrCreate(['name' => $name], ['slug' => str($name)->slug()]);
        }
    }
}
