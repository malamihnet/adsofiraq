<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasPlatformVerification;

    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'brand_campaign')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
