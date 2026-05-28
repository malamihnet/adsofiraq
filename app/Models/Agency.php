<?php

namespace App\Models;

use App\Models\Concerns\HasAuthorityProfile;
use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Agency extends Model
{
    use HasAuthorityProfile, HasPlatformVerification;

    protected $fillable = [
        'name',
        'slug',
        'is_production_house',
        'bio',
        'website_url',
        'logo_path',
        'cover_path',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'twitter_url',
        'founded_year',
        'meta_title',
        'meta_description',
        'ranking_score',
    ];

    protected function casts(): array
    {
        return [
            'is_production_house' => 'boolean',
            'founded_year' => 'integer',
            'ranking_score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agency $agency) {
            if (empty($agency->slug)) {
                $agency->slug = Str::slug($agency->name);
            }
        });
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'agency_campaign')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
