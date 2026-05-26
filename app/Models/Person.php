<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Person extends Model
{
    use HasPlatformVerification, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'name',
        'slug',
        'position',
        'photo_path',
        'bio',
        'website_url',
        'official_profile_url',
        'work_1',
        'work_2',
        'work_3',
        'status',
        'submission_notes',
        'submitted_by',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Person $person) {
            if (empty($person->slug)) {
                $person->slug = static::generateUniqueSlug($person->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->approved();
    }

    public function scopeStatusFilter(Builder $query, ?string $status): Builder
    {
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            return Storage::disk('public')->url($this->photo_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=0a0a0a&color=fff&size=512';
    }

    public function getFeaturedWorksAttribute(): array
    {
        return array_values(array_filter([
            $this->work_1,
            $this->work_2,
            $this->work_3,
        ]));
    }

    public function getProfileLinkAttribute(): ?string
    {
        return $this->official_profile_url ?: $this->website_url;
    }

    public function getSeoDescriptionAttribute(): string
    {
        if ($this->bio) {
            return Str::limit(strip_tags($this->bio), 160);
        }

        return $this->name.' — '.$this->position.' on Ads of Iraq.';
    }
}
