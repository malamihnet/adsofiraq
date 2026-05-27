<?php

namespace App\Models;

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

    protected function normalizedFilePath(): ?string
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

    protected function placeholderUrl(): string
    {
        $webp = config('upload.placeholder', 'images/placeholder.webp');

        if (file_exists(public_path($webp))) {
            return asset($webp);
        }

        return asset(config('upload.placeholder_fallback', 'images/placeholder.jpg'));
    }
}
