<?php

namespace App\Enums;

enum CompanyRankingProfile: string
{
    case ProductionHouse = 'production_house';
    case Agency = 'agency';
    case PostProductionHouse = 'post_production_house';
    case CreativeStudio = 'creative_studio';
    case AnimationStudio = 'animation_studio';
    case SoundHouse = 'sound_house';

    public function configKey(): string
    {
        return $this->value;
    }

    public function cacheKey(int $limit): string
    {
        return "rankings.top_companies.{$this->value}.{$limit}";
    }
}
