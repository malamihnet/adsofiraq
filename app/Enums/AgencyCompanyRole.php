<?php

namespace App\Enums;

enum AgencyCompanyRole: string
{
    case Agency = 'agency';
    case ProductionHouse = 'production_house';
    case PostProductionHouse = 'post_production_house';
    case CreativeStudio = 'creative_studio';
    case AnimationStudio = 'animation_studio';
    case SoundHouse = 'sound_house';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Agency',
            self::ProductionHouse => 'Production House',
            self::PostProductionHouse => 'Post-Production House',
            self::CreativeStudio => 'Creative Studio',
            self::AnimationStudio => 'Animation Studio',
            self::SoundHouse => 'Sound House',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
