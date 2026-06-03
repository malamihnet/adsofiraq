<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'source',
        'campaigns_count',
    ];

    protected function casts(): array
    {
        return [
            'campaigns_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateUniqueSlug($tag->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_tag')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->name.' Tag | Ads Of Iraq';
    }

    public function getSeoDescriptionAttribute(): string
    {
        return sprintf(
            'Browse Iraqi advertising campaigns tagged %s on Ads Of Iraq.',
            $this->name,
        );
    }
}
