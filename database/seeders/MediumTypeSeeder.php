<?php

namespace Database\Seeders;

use App\Models\MediumType;
use Illuminate\Database\Seeder;

class MediumTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'TV Commercial', 'Digital Video', 'Print', 'Outdoor / OOH',
            'Radio', 'Social Media', 'Integrated Campaign', 'Branded Content',
            'Animation', 'Photography', 'Design / Visual Identity',
        ];

        foreach ($types as $name) {
            MediumType::firstOrCreate(['name' => $name], ['slug' => str($name)->slug()]);
        }
    }
}
