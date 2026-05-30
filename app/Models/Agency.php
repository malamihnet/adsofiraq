<?php

namespace App\Models;

use App\Enums\AgencyCampaignRole;
use App\Enums\AgencyCompanyRole;
use App\Models\Concerns\HasAuthorityProfile;
use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Agency extends Model
{
    use HasAuthorityProfile, HasPlatformVerification;

    protected $fillable = [
        'name',
        'slug',
        'is_production_house',
        'bio',
        'website_url',
        'logo_path',
        'cover_path',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'twitter_url',
        'founded_year',
        'meta_title',
        'meta_description',
        'ranking_score',
    ];

    protected function casts(): array
    {
        return [
            'is_production_house' => 'boolean',
            'founded_year' => 'integer',
            'ranking_score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agency $agency) {
            if (empty($agency->slug)) {
                $agency->slug = Str::slug($agency->name);
            }
        });
    }

    public function roles(): HasMany
    {
        return $this->hasMany(AgencyRole::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function agencyCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', AgencyCampaignRole::Agency->value);
    }

    public function productionHouseCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', AgencyCampaignRole::ProductionHouse->value);
    }

    public function hasRole(AgencyCompanyRole|string $role): bool
    {
        $value = $role instanceof AgencyCompanyRole ? $role->value : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('role', $value);
        }

        return $this->roles()->where('role', $value)->exists();
    }

    public function isProductionHouse(): bool
    {
        return $this->hasRole(AgencyCompanyRole::ProductionHouse) || $this->is_production_house;
    }

    public function isAgency(): bool
    {
        if ($this->relationLoaded('roles')) {
            if ($this->roles->isEmpty()) {
                return true;
            }

            return $this->roles->contains('role', AgencyCompanyRole::Agency->value);
        }

        if (! $this->roles()->exists()) {
            return true;
        }

        return $this->hasRole(AgencyCompanyRole::Agency);
    }

    /**
     * @return list<string>
     */
    public function roleLabels(): array
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();

        if ($roles->isEmpty()) {
            $labels = [];

            if ($this->isAgency()) {
                $labels[] = AgencyCompanyRole::Agency->label();
            }

            if ($this->isProductionHouse()) {
                $labels[] = AgencyCompanyRole::ProductionHouse->label();
            }

            return $labels;
        }

        return $roles
            ->map(fn (AgencyRole $record) => AgencyCompanyRole::from($record->role)->label())
            ->values()
            ->all();
    }

    public function profileSubtitle(): string
    {
        $labels = $this->roleLabels();

        return $labels !== [] ? implode(' · ', $labels) : 'Agency';
    }

    public function scopeWithCompanyRole(Builder $query, AgencyCompanyRole $role): Builder
    {
        return $query->whereHas('roles', fn (Builder $roles) => $roles->where('role', $role->value));
    }

    public function scopeForProductionHouseSelect(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->where('is_production_house', true)
                ->orWhereHas('roles', fn (Builder $roles) => $roles->where('role', AgencyCompanyRole::ProductionHouse->value));
        });
    }

    public function scopeForTopAgencies(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->whereHas('roles', fn (Builder $roles) => $roles->where('role', AgencyCompanyRole::Agency->value))
                ->orWhereDoesntHave('roles');
        });
    }

    public function scopeForTopProductionHouses(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->where('is_production_house', true)
                ->orWhereHas('roles', fn (Builder $roles) => $roles->where('role', AgencyCompanyRole::ProductionHouse->value));
        });
    }

    /**
     * Count distinct approved campaigns for production-house rankings:
     * pivot role=production_house, plus legacy pivot role=agency when marked as production house.
     */
    public function scopeWithRankableProductionHouseCampaignCount(Builder $query): Builder
    {
        $productionHousePivot = AgencyCampaignRole::ProductionHouse->value;
        $agencyPivot = AgencyCampaignRole::Agency->value;
        $productionHouseCompanyRole = AgencyCompanyRole::ProductionHouse->value;

        return $query->select('agencies.*')->selectSub(function ($sub) use ($productionHousePivot, $agencyPivot, $productionHouseCompanyRole) {
            $sub->from('agency_campaign')
                ->join('campaigns', 'campaigns.id', '=', 'agency_campaign.campaign_id')
                ->whereColumn('agency_campaign.agency_id', 'agencies.id')
                ->where('campaigns.status', 'approved')
                ->where('campaigns.is_draft', false)
                ->where(function ($roleQuery) use ($productionHousePivot, $agencyPivot, $productionHouseCompanyRole) {
                    $roleQuery->where('agency_campaign.role', $productionHousePivot)
                        ->orWhere(function ($legacy) use ($agencyPivot, $productionHouseCompanyRole) {
                            $legacy->where('agency_campaign.role', $agencyPivot)
                                ->where(function ($phAgency) use ($productionHouseCompanyRole) {
                                    $phAgency->where('agencies.is_production_house', true)
                                        ->orWhereExists(function ($roles) use ($productionHouseCompanyRole) {
                                            $roles->select(DB::raw(1))
                                                ->from('agency_roles')
                                                ->whereColumn('agency_roles.agency_id', 'agencies.id')
                                                ->where('agency_roles.role', $productionHouseCompanyRole);
                                        });
                                });
                        });
                })
                ->selectRaw('count(distinct campaigns.id)');
        }, 'production_house_campaigns_count');
    }

    public function getSeoTitleAttribute(): string
    {
        if ($this->meta_title) {
            return $this->meta_title;
        }

        $isAgency = $this->isAgency();
        $isProductionHouse = $this->isProductionHouse();

        if ($isAgency && $isProductionHouse) {
            return $this->name.' Creative Agency & Production House | Ads of Iraq';
        }

        if ($isProductionHouse) {
            return $this->name.' Production House | Ads of Iraq';
        }

        if ($isAgency) {
            return $this->name.' Agency | Ads of Iraq';
        }

        $labels = $this->roleLabels();

        if ($labels !== []) {
            return $this->name.' '.($labels[0]).' | Ads of Iraq';
        }

        return $this->name.' — Ads of Iraq';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
