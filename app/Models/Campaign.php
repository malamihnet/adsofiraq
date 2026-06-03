<?php

namespace App\Models;

use App\Enums\AgencyCampaignRole;
use App\Support\Placeholder;
use App\Models\Concerns\HasPlatformVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasPlatformVerification, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'published_at',
        'description',
        'credits',
        'video_url',
        'video_provider',
        'video_file_path',
        'video_type',
        'thumbnail_path',
        'status',
        'approved_at',
        'is_featured',
        'is_hero',
        'hero_order',
        'manual_order',
        'archive_placement_enabled',
        'archive_page',
        'archive_position',
        'is_student',
        'is_nsfw',
        'is_draft',
        'needs_changes',
        'editorial_label',
        'ai_summary',
        'is_made_by_iraq',
        'ranking_score',
        'views_count',
        'bookmarks_count',
        'watchers_count',
        'admin_notes',
        'submission_notes',
        'source_url',
        'source_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'approved_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_hero' => 'boolean',
            'hero_order' => 'integer',
            'manual_order' => 'integer',
            'archive_placement_enabled' => 'boolean',
            'archive_page' => 'integer',
            'archive_position' => 'integer',
            'is_student' => 'boolean',
            'is_nsfw' => 'boolean',
            'is_draft' => 'boolean',
            'needs_changes' => 'boolean',
            'is_made_by_iraq' => 'boolean',
            'ranking_score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = static::generateUniqueSlug($campaign->title);
            }

            if ($campaign->status === 'approved' && $campaign->approved_at === null) {
                $campaign->approved_at = now();
            }
        });

        static::updating(function (Campaign $campaign) {
            if ($campaign->isDirty('status')) {
                if ($campaign->status === 'approved') {
                    if ($campaign->getOriginal('status') !== 'approved') {
                        $campaign->approved_at = now();
                    }
                } else {
                    $campaign->approved_at = null;
                    $campaign->is_hero = false;
                    $campaign->hero_order = null;
                    $campaign->manual_order = null;
                    $campaign->archive_placement_enabled = false;
                    $campaign->archive_page = null;
                    $campaign->archive_position = null;
                }
            }

            if ($campaign->status !== 'approved') {
                $campaign->manual_order = null;
                $campaign->archive_placement_enabled = false;
                $campaign->archive_page = null;
                $campaign->archive_position = null;
            }
        });

        static::saved(function (Campaign $campaign) {
            if ($campaign->wasChanged([
                'manual_order',
                'status',
                'approved_at',
                'archive_placement_enabled',
                'archive_page',
                'archive_position',
            ])) {
                app(\App\Services\CampaignArchiveOrderingService::class)->clearCache();
            }
        });

        static::deleted(function () {
            app(\App\Services\CampaignArchiveOrderingService::class)->clearCache();
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeHero(Builder $query): Builder
    {
        return $query->where('is_hero', true);
    }

    public function scopeOrderedForHero(Builder $query): Builder
    {
        return $query
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->orderByRaw('CASE WHEN hero_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('hero_order');
    }

    public function scopeLatestOnPlatform(Builder $query): Builder
    {
        return $query
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at');
    }

    public function scopeArchivePlaced(Builder $query): Builder
    {
        return $query
            ->where('archive_placement_enabled', true)
            ->whereNotNull('archive_page')
            ->whereNotNull('archive_position');
    }

    public function scopeArchiveAutomatic(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('archive_placement_enabled', false)
                ->orWhereNull('archive_page')
                ->orWhereNull('archive_position');
        });
    }

    /** @deprecated Use scopeArchivePlaced() */
    public function scopePinned(Builder $query): Builder
    {
        return $query->archivePlaced();
    }

    /** @deprecated Use scopeArchiveAutomatic() */
    public function scopeAutomaticArchive(Builder $query): Builder
    {
        return $query->archiveAutomatic();
    }

    public function scopeOrderedForArchive(Builder $query): Builder
    {
        return $query->latestOnPlatform();
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->approved()->where('is_draft', false);
    }

    public function scopeMadeByIraq(Builder $query): Builder
    {
        return $query->where('is_made_by_iraq', true);
    }

    public function getEditorialLabelDisplayAttribute(): ?string
    {
        if (! $this->editorial_label) {
            return null;
        }

        return config('authority.editorial_labels.'.$this->editorial_label, $this->editorial_label);
    }

    public function getWorkflowStatusLabelAttribute(): string
    {
        if ($this->is_draft) {
            return 'Draft';
        }

        if ($this->needs_changes || $this->status === 'needs_changes') {
            return 'Needs Changes';
        }

        return match ($this->status) {
            'pending' => 'Under Review',
            'approved' => $this->is_featured ? 'Featured' : 'Approved',
            'rejected' => 'Rejected',
            'draft' => 'Draft',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $authId = (int) $user->id;

        foreach (['user_id', 'submitted_by', 'created_by'] as $ownerColumn) {
            $ownerId = $this->getAttribute($ownerColumn);

            if ($ownerId !== null && $authId === (int) $ownerId) {
                return true;
            }
        }

        return false;
    }

    public function awardWins(): HasMany
    {
        return $this->hasMany(AwardWinner::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CampaignRevision::class);
    }

    public function pendingRevision(): HasOne
    {
        return $this->hasOne(CampaignRevision::class)->where('status', 'pending')->latest('id');
    }

    public function hasPendingRevision(): bool
    {
        return $this->pendingRevision()->exists();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?: $this->getRouteKeyName();

        return static::query()->where($field, $value)->first();
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_campaign')->withTimestamps();
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', AgencyCampaignRole::Agency->value);
    }

    public function productionHouses(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', AgencyCampaignRole::ProductionHouse->value);
    }

    public function allAgencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_campaign')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'campaign_industry')->withTimestamps();
    }

    public function mediumTypes(): BelongsToMany
    {
        return $this->belongsToMany(MediumType::class, 'campaign_medium_type')->withTimestamps();
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'campaign_country')->withTimestamps();
    }

    public function getBrandAttribute(): ?Brand
    {
        return $this->relationLoaded('brands') ? $this->brands->first() : $this->brands()->first();
    }

    public function getAgencyAttribute(): ?Agency
    {
        return $this->relationLoaded('agencies') ? $this->agencies->first() : $this->agencies()->first();
    }

    public function getProductionHouseAttribute(): ?Agency
    {
        return $this->relationLoaded('productionHouses')
            ? $this->productionHouses->first()
            : $this->productionHouses()->first();
    }

    public function getIndustryAttribute(): ?Industry
    {
        return $this->relationLoaded('industries') ? $this->industries->first() : $this->industries()->first();
    }

    public function getMediumTypeAttribute(): ?MediumType
    {
        return $this->relationLoaded('mediumTypes') ? $this->mediumTypes->first() : $this->mediumTypes()->first();
    }

    public function getCountryAttribute(): ?Country
    {
        return $this->relationLoaded('countries') ? $this->countries->first() : $this->countries()->first();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(CampaignAsset::class)->orderBy('sort_order');
    }

    /**
     * Image stills for the public gallery (deduplicated by path, source URL, and content hash).
     *
     * @return \Illuminate\Support\Collection<int, CampaignAsset>
     */
    public function galleryStills(): \Illuminate\Support\Collection
    {
        return app(\App\Services\CampaignMediaDeduplicationService::class)->galleryStillsFor($this);
    }

    protected function galleryPathKey(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $normalized = $this->normalizeThumbnailPath($path) ?? trim($path);

        if (filter_var($normalized, FILTER_VALIDATE_URL)) {
            return strtolower($normalized);
        }

        return strtolower(ltrim(str_replace('\\', '/', $normalized), '/'));
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CampaignVideo::class)->orderBy('sort_order');
    }

    /**
     * @return \Illuminate\Support\Collection<int, CampaignVideo>
     */
    public function resolvedVideos(): \Illuminate\Support\Collection
    {
        if (! $this->relationLoaded('videos')) {
            $this->load('videos');
        }

        if ($this->videos->isNotEmpty()) {
            return $this->videos;
        }

        $legacy = $this->legacyVideoRecord();

        return $legacy ? collect([$legacy]) : collect();
    }

    public function primaryVideo(): ?CampaignVideo
    {
        return $this->resolvedVideos()->first();
    }

  /**
     * @return array{url: string, provider: string}|null
     */
    public function firstRemoteVideoForThumbnail(): ?array
    {
        foreach ($this->resolvedVideos() as $video) {
            if (in_array($video->type, ['youtube', 'vimeo'], true) && ! empty($video->url)) {
                return ['url' => $video->url, 'provider' => $video->type];
            }
        }

        return null;
    }

    public function firstFileVideoForThumbnail(): ?CampaignVideo
    {
        return $this->resolvedVideos()->first(
            fn (CampaignVideo $video) => $video->type === 'file' && ! empty($video->file_path)
        );
    }

    protected function legacyVideoRecord(): ?CampaignVideo
    {
        $type = $this->video_type ?? $this->video_provider;

        if ($type === 'file' && ! empty($this->video_file_path)) {
            return new CampaignVideo([
                'campaign_id' => $this->id,
                'type' => 'file',
                'file_path' => $this->video_file_path,
                'sort_order' => 1,
            ]);
        }

        if (in_array($type, ['youtube', 'vimeo'], true) && ! empty($this->video_url)) {
            return new CampaignVideo([
                'campaign_id' => $this->id,
                'type' => $type,
                'url' => $this->video_url,
                'sort_order' => 1,
            ]);
        }

        return null;
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(CampaignWatcher::class);
    }

    public function watchedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campaign_watchers')->withTimestamps();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    public function isWatchedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->watchers()->where('user_id', $user->id)->exists();
    }

    /**
     * Public URL for the campaign thumbnail.
     *
     * Always use this accessor in views — never reference thumbnail_path directly.
     * Resolves paths saved as relative, public/, or storage/ prefixed values.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $url = $this->resolveStoredThumbnailUrl();

        if ($url) {
            return $url;
        }

        return $this->placeholderThumbnailUrl();
    }

    /**
     * Resolve a stored thumbnail_path to a public URL, or null when unavailable.
     */
    protected function resolveStoredThumbnailUrl(): ?string
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        $path = $this->normalizeThumbnailPath($this->thumbnail_path);

        if ($path === null) {
            return null;
        }

        // Absolute URL stored in the database.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Path already includes the public storage prefix (e.g. storage/campaigns/...).
        if (str_starts_with($path, 'storage/')) {
            $relativePath = substr($path, strlen('storage/'));

            if (Storage::disk('public')->exists($relativePath)) {
                return asset($path);
            }

            return $this->placeholderThumbnailUrl();
        }

        // Standard relative path on the public disk (e.g. campaigns/thumbnails/file.jpg).
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return $this->placeholderThumbnailUrl();
    }

    /**
     * Normalize thumbnail_path values from uploads, imports, or legacy formats.
     */
    protected function normalizeThumbnailPath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        $prefixes = [
            'public/storage/',
            'public/',
            'storage/app/public/',
            'app/public/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return $path;
    }

    protected function placeholderThumbnailUrl(): string
    {
        return Placeholder::url('landscape');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $video = $this->resolvedVideos()->first(
            fn (CampaignVideo $video) => in_array($video->type, ['youtube', 'vimeo'], true)
        );

        return $video?->embed_url;
    }

    public function getVideoFileUrlAttribute(): ?string
    {
        $video = $this->resolvedVideos()->first(
            fn (CampaignVideo $video) => $video->type === 'file'
        );

        return $video?->file_url;
    }

    public function getVideoMimeTypeAttribute(): ?string
    {
        $video = $this->resolvedVideos()->first(
            fn (CampaignVideo $video) => $video->type === 'file'
        );

        return $video?->mime_type;
    }

    public function hasUploadedVideo(): bool
    {
        return $this->resolvedVideos()->contains(
            fn (CampaignVideo $video) => $video->type === 'file' && $video->file_url !== null
        );
    }

    public function hasExternalVideo(): bool
    {
        return $this->resolvedVideos()->contains(
            fn (CampaignVideo $video) => in_array($video->type, ['youtube', 'vimeo'], true) && ! empty($video->url)
        );
    }

    public function hasVideos(): bool
    {
        return $this->resolvedVideos()->isNotEmpty();
    }

    public function getSeoDescriptionAttribute(): string
    {
        return Str::limit(strip_tags($this->description), 160);
    }
}
