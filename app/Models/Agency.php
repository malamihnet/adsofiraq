<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Agency extends Model
{
    use HasPlatformVerification;

    protected $fillable = ['name', 'slug'];

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
