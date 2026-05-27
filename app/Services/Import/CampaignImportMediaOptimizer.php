<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Storage;

class CampaignImportMediaOptimizer
{
    /**
     * Store image bytes as-is at an exact relative path on the public disk (no format conversion).
     */
    public function storeImageAtPath(string $binary, string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        Storage::disk('public')->makeDirectory(dirname($relativePath));
        Storage::disk('public')->put($relativePath, $binary);

        return Storage::disk('public')->exists($relativePath) ? $relativePath : null;
    }
}
