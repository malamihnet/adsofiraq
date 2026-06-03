<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    protected array $names = [
        'Director',
        'Film Director',
        'Creative Director',
        'Executive Producer',
        'Producer',
        'Associate Producer',
        'Line Producer',
        'Production Manager',
        'Production Coordinator',
        'Director of Photography',
        'DOP',
        'Camera Operator',
        '1st AC',
        '2nd AC',
        'Gaffer',
        'Best Boy',
        'Key Grip',
        'Grip',
        'Drone Operator',
        'Editor',
        'Senior Editor',
        'Assistant Editor',
        'Colorist',
        'Motion Designer',
        'Motion Graphics Artist',
        'Animator',
        '3D Artist',
        'VFX Artist',
        'Compositor',
        'Sound Designer',
        'Audio Engineer',
        'Music Producer',
        'Voice Over Artist',
        'Photographer',
        'Retoucher',
        'Marketing Manager',
        'Brand Manager',
        'Creative Strategist',
        'Account Manager',
        'Project Manager',
        'Social Media Manager',
        'Community Manager',
        'Media Buyer',
        'Performance Marketer',
        'Copywriter',
        'Content Creator',
        'Content Writer',
        'Graphic Designer',
        'Art Director',
        'PR Manager',
    ];

    public function run(): void
    {
        foreach ($this->names as $index => $name) {
            Position::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index + 1],
            );
        }
    }
}
