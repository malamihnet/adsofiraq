<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPlatformVerification
{
    public function initializeHasPlatformVerification(): void
    {
        $this->casts['is_verified'] = 'boolean';
        $this->casts['verified_at'] = 'datetime';
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeNotVerified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopePlatformVerificationFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'verified' => $query->verified(),
            'unverified' => $query->notVerified(),
            default => $query,
        };
    }
}
