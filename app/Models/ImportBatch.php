<?php

namespace App\Models;

use App\Services\Import\CampaignUrlNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ImportBatch extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'country_url',
        'status',
        'total_urls',
        'crawl_max_page',
        'crawl_next_page',
        'consecutive_existing',
        'stop_after_existing',
        'queue_order_mode',
        'purpose',
        'import_mode',
        'imported_count',
        'failed_count',
        'skipped_count',
        'existing_skipped_count',
        'started_at',
        'completed_at',
        'deleted_at',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ImportBatch $batch) {
            if (empty($batch->id)) {
                $batch->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(ImportQueueItem::class, 'batch_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'source_batch_id');
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function refreshCounts(): void
    {
        $this->update([
            'imported_count' => $this->queueItems()->where('status', 'done')->count(),
            'failed_count' => $this->queueItems()->where('status', 'failed')->count(),
            'skipped_count' => $this->queueItems()->where('status', 'skipped')->count(),
        ]);
    }

    /**
     * @param  list<array{url: string, page: int, sort_order: int}>  $entries
     */
    public function enqueueDiscoveredUrls(array $entries): void
    {
        foreach ($entries as $entry) {
            $normalized = app(CampaignUrlNormalizer::class)->normalize($entry['url']);

            if ($this->queueItems()->where('url', $normalized)->exists()) {
                continue;
            }

            $this->queueItems()->create([
                'url' => $normalized,
                'status' => 'pending',
                'page_number' => $entry['page'],
                'sort_order' => $entry['sort_order'],
            ]);
        }
    }
}
