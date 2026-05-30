<?php

namespace App\Models;

use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CampaignAsset extends Model
{
    protected $fillable = [
        'campaign_id',
        'file_path',
        'file_type',
        'sort_order',
        'source_url',
        'source_url_key',
        'content_hash',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getUrlAttribute(): string
    {
        return $this->resolvedUrl() ?? $this->placeholderUrl();
    }

    public function resolvedUrl(): ?string
    {
        $path = $this->normalizedFilePath();

        if ($path === null) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return null;
    }

    public function isDisplayableImage(): bool
    {
        if ($this->resolvedUrl() === null) {
            return false;
        }

        $type = strtolower((string) $this->file_type);

        if ($type === 'image' || $type === '') {
            return true;
        }

        $path = strtolower($this->normalizedFilePath() ?? '');

        return (bool) preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path);
    }

    public function isWebpFile(): bool
    {
        $path = strtolower($this->normalizedFilePath() ?? '');

        return str_ends_with($path, '.webp');
    }

    public function galleryPathKey(): ?string
    {
        $path = $this->normalizedFilePath();

        if ($path === null) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return strtolower($path);
        }

        return strtolower(ltrim($path, '/'));
    }

    public function normalizedFilePath(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        $path = str_replace('\\', '/', trim($this->file_path));

        if ($path === '') {
            return null;
        }

        $prefixes = [
            'public/storage/',
            'public/',
            'storage/app/public/',
            'app/public/',
            'storage/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return ltrim($path, '/');
    }

    public function effectiveContentHash(): ?string
    {
        return app(CampaignMediaDeduplicationService::class)->resolveStillContentHash($this);
    }

    protected function placeholderUrl(): string
    {
        return placeholderUrl('landscape');
    }
}
