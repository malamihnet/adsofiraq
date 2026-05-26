<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Industry extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (Industry $industry) {
            if (empty($industry->slug)) {
                $industry->slug = Str::slug($industry->name);
            }
        });
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_industry')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
