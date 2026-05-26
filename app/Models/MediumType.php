<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MediumType extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (MediumType $mediumType) {
            if (empty($mediumType->slug)) {
                $mediumType->slug = Str::slug($mediumType->name);
            }
        });
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_medium_type')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
