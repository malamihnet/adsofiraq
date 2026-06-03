<?php

namespace App\Enums;

enum PositionCategory: string
{
    case Production = 'production';
    case CameraLighting = 'camera_lighting';
    case ArtStyling = 'art_styling';
    case PostProduction = 'post_production';
    case AgencyCreative = 'agency_creative';
    case BrandClient = 'brand_client';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Production',
            self::CameraLighting => 'Camera & Lighting',
            self::ArtStyling => 'Art & Styling',
            self::PostProduction => 'Post-Production',
            self::AgencyCreative => 'Agency & Creative',
            self::BrandClient => 'Brand / Client',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function tryFromMixed(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Other;
        }

        return self::tryFrom($value) ?? self::Other;
    }
}
