<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CampaignRevision extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'revision_payload',
        'status',
        'review_notes',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'revision_payload' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'review_notes' => $notes,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'review_notes' => $notes,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}

