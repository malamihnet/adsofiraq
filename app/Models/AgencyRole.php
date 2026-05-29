<?php

namespace App\Models;

use App\Enums\AgencyCompanyRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyRole extends Model
{
    protected $fillable = [
        'agency_id',
        'role',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function companyRole(): AgencyCompanyRole
    {
        return AgencyCompanyRole::from($this->role);
    }
}
