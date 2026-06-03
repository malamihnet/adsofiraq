<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 1;

        foreach (PositionCatalog::grouped() as $category => $names) {
            foreach ($names as $name) {
                Position::query()->updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'category' => $category,
                        'sort_order' => $sortOrder++,
                    ],
                );
            }
        }
    }
}
