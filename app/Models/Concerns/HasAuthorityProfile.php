<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

trait HasAuthorityProfile
{
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
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
        return $this->meta_title ?: ($this->name.' — Ads of Iraq');
    }

    public function getSeoDescriptionAttribute(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        $count = $this->approved_campaigns_count ?? $this->campaigns()->approved()->count();

        return sprintf(
            'Explore %d approved advertising campaigns by %s on Ads of Iraq — Iraq’s curated archive of creative work.',
            $count,
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
     * @return array{campaigns: int, views: int, bookmarks: int, years_active: list<int>}
     */
    public function aggregateStats(): array
    {
        $stats = $this->approvedCampaignsQuery()
            ->selectRaw('COUNT(*) as campaigns')
            ->selectRaw('COALESCE(SUM(views_count), 0) as views')
            ->selectRaw('COALESCE(SUM(bookmarks_count), 0) as bookmarks')
            ->first();

        $years = $this->approvedCampaignsQuery()
            ->whereNotNull('published_at')
            ->get(['published_at'])
            ->map(fn ($campaign) => (int) $campaign->published_at->format('Y'))
            ->unique()
            ->sortDesc()
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
