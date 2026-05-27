<?php

namespace App\Services\Import;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampaignImportSlugService
{
    public function preferredFromSourceUrl(string $sourceUrl): ?string
    {
        $path = parse_url($sourceUrl, PHP_URL_PATH) ?? '';

        if (preg_match('#/campaigns/([a-z0-9\-]+)/?$#i', $path, $matches)) {
            $segment = $matches[1];

            if (in_array(strtolower($segment), ['new'], true)) {
                return null;
            }

            $slug = Str::slug($segment);

            return $slug !== '' ? $slug : null;
        }

        return null;
    }

    /**
     * Resolve a unique campaign slug for imports (prefers AOTW URL segment when available).
     */
    public function resolveForImport(string $title, string $sourceUrl): string
    {
        $preferred = $this->preferredFromSourceUrl($sourceUrl);
        $base = $preferred ?: Str::slug($title);

        if ($base === '') {
            $base = 'imported-campaign';
        }

        if (! Campaign::withTrashed()->where('slug', $base)->exists()) {
            return $base;
        }

        $suffix = 2;
        $slug = $base.'-'.$suffix;

        while (Campaign::withTrashed()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;

            if ($suffix > 5000) {
                $slug = $base.'-'.now()->timestamp;
                break;
            }
        }

        Log::info("Slug collision resolved: {$base} → {$slug}", [
            'source_url' => $sourceUrl,
            'title' => $title,
        ]);

        return $slug;
    }

    public function slugExists(string $slug): bool
    {
        return Campaign::withTrashed()->where('slug', $slug)->exists();
    }
}
