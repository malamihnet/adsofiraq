<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CampaignVideo extends Model
{
    protected $fillable = [
        'campaign_id',
        'type',
        'url',
        'file_path',
        'title',
        'sort_order',
        'embed_key',
        'source_url_key',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if (empty($this->url) || ! in_array($this->type, ['youtube', 'vimeo'], true)) {
            return null;
        }

        if ($this->type === 'youtube') {
            preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->url, $matches);

            return isset($matches[1]) ? 'https://www.youtube.com/embed/'.$matches[1] : null;
        }

        preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $this->url, $matches);

        return isset($matches[1]) ? 'https://player.vimeo.com/video/'.$matches[1] : null;
    }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->type !== 'file' || empty($this->file_path)) {
            return null;
        }

        $path = $this->normalizePath($this->file_path);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function getMimeTypeAttribute(): ?string
    {
        if ($this->type !== 'file' || empty($this->file_path)) {
            return null;
        }

        return match (strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION))) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => null,
        };
    }

    public function getDisplayTitleAttribute(): string
    {
        if (! empty($this->title)) {
            return $this->title;
        }

        return match ($this->type) {
            'file' => 'Uploaded video',
            'youtube' => 'YouTube video',
            'vimeo' => 'Vimeo video',
            default => 'Video',
        };
    }

    public function getPosterUrlAttribute(): ?string
    {
        // Reserved for per-video generated thumbnails.
        return null;
    }

    public function getEmbedIdAttribute(): ?string
    {
        if ($this->type === 'youtube' && ! empty($this->url)) {
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->url, $matches)) {
                return $matches[1];
            }
        }

        if ($this->type === 'vimeo' && ! empty($this->url)) {
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $this->url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function getPreviewThumbnailAttribute(): ?string
    {
        if ($this->type === 'youtube' && $this->embed_url) {
            if (preg_match('/embed\/([a-zA-Z0-9_-]{11})/', $this->embed_url, $matches)) {
                return 'https://img.youtube.com/vi/'.$matches[1].'/mqdefault.jpg';
            }
        }

        if ($this->type === 'vimeo' && $this->embed_url) {
            if (preg_match('/video\/(\d+)/', $this->embed_url, $matches)) {
                return 'https://vumbnail.com/'.$matches[1].'.jpg';
            }
        }

        return null;
    }

    public function isPlayable(): bool
    {
        return ($this->type === 'file' && $this->file_url !== null)
            || ($this->embed_url !== null);
    }

    protected function normalizePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        foreach (['public/storage/', 'public/', 'storage/app/public/', 'app/public/', 'storage/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }
}
