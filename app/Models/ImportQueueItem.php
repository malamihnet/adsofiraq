<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportQueueItem extends Model
{
    protected $table = 'import_queue';

    protected $fillable = [
        'batch_id',
        'url',
        'status',
        'error_message',
        'campaign_id',
        'page_number',
        'sort_order',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
