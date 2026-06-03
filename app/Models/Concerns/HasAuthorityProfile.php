<?php

namespace App\Models\Concerns;

use App\Models\Campaign;
use App\Support\Placeholder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait HasAuthorityProfile
{
    public function getLogoUrlAttribute(): string
    {
        if (empty($this->logo_path)) {
            return Placeholder::url('square');
        }

        $path = ltrim(str_replace('\\', '/', $this->logo_path), '/');

        if (! Storage::disk('public')->exists($path)) {
            return Placeholder::url('square');
        }

        return asset('storage/'.$path);
    }

    public function hasLogo(): bool
    {
        if (empty($this->logo_path)) {
            return false;
        }

        $path = ltrim(str_replace('\\', '/', $this->logo_path), '/');

        return Storage::disk('public')->exists($path);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_path);
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: ($this->name.' | Ads of Iraq');
    }

    public function getSeoDescriptionAttribute(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        $count = $this->approved_campaigns_count ?? $this->approvedCampaignStatsQuery()->value('campaigns');

        return sprintf(
            'Explore %d approved advertising campaigns by %s on Ads of Iraq, Iraq\'s curated archive of creative work.',
            (int) $count,
            $this->name,
        );
    }

    public function approvedCampaignsQuery(): BelongsToMany
    {
        return $this->campaigns()
            ->approved()
            ->where('is_draft', false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Campaign>
     */
    public function approvedCampaignsForScoring(): \Illuminate\Database\Eloquent\Collection
    {
        return Campaign::query()
            ->approved()
            ->where('campaigns.is_draft', false)
            ->whereIn('campaigns.id', fn (Builder $query) => $this->applyApprovedCampaignIdsSubquery($query))
            ->get();
    }

    /**
     * Aggregate stats without pivot columns (MySQL ONLY_FULL_GROUP_BY safe).
     */
    protected function approvedCampaignStatsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Campaign::query()
            ->approved()
            ->where('campaigns.is_draft', false)
            ->whereIn('campaigns.id', fn (Builder $query) => $this->applyApprovedCampaignIdsSubquery($query))
            ->select([
                DB::raw('COUNT(DISTINCT campaigns.id) as campaigns'),
                DB::raw('COALESCE(SUM(campaigns.views_count), 0) as views'),
                DB::raw('COALESCE(SUM(campaigns.bookmarks_count), 0) as bookmarks'),
            ]);
    }

    protected function applyApprovedCampaignIdsSubquery(Builder $query): void
    {
        $relation = $this->campaigns();

        $query
            ->select($relation->getRelatedPivotKeyName())
            ->from($relation->getTable())
            ->where($relation->getForeignPivotKeyName(), $this->getKey())
            ->distinct();
    }

    /**
     * @return array{campaigns: int, views: int, bookmarks: int, years_active: list<int>}
     */
    public function aggregateStats(): array
    {
        $stats = $this->approvedCampaignStatsQuery()->toBase()->first();

        $years = Campaign::query()
            ->approved()
            ->where('campaigns.is_draft', false)
            ->whereIn('campaigns.id', fn (Builder $query) => $this->applyApprovedCampaignIdsSubquery($query))
            ->whereNotNull('campaigns.published_at')
            ->selectRaw('DISTINCT YEAR(campaigns.published_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();

        return [
            'campaigns' => (int) ($stats->campaigns ?? 0),
            'views' => (int) ($stats->views ?? 0),
            'bookmarks' => (int) ($stats->bookmarks ?? 0),
            'years_active' => $years,
        ];
    }
}
