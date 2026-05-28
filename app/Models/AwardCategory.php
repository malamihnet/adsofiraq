<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends Model
{
    protected $fillable = [
        'award_id',
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(AwardWinner::class);
    }
}
