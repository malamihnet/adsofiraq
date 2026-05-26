<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndustrySeeder::class,
            MediumTypeSeeder::class,
            CountrySeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
