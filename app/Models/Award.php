<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Award extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'year',
        'description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Award $award) {
            if (empty($award->slug)) {
                $award->slug = Str::slug($award->title.'-'.$award->year);
            }
        });
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AwardCategory::class)->orderBy('sort_order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
