<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class CampaignImportMediaOptimizer
{
    protected ?ImageManager $imageManager = null;

    /**
     * Store image bytes as WebP at an exact relative path on the public disk.
     */
    public function storeImageAsWebpAtPath(string $binary, string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        Storage::disk('public')->makeDirectory(dirname($relativePath));

        $maxWidth = (int) config('import.image_max_width', 2560);
        $quality = (int) config('import.webp_quality', 82);

        try {
            $image = $this->imageManager()->read($binary);
            $width = $image->width();

            if ($width > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            Storage::disk('public')->put(
                $relativePath,
                (string) $image->encode(new WebpEncoder(quality: $quality))
            );

            if (Storage::disk('public')->missing($relativePath)) {
                return null;
            }

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('Campaign import: image WebP conversion failed.', [
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Store raw image bytes without conversion (fallback when WebP fails).
     */
    public function storeRawImageAtPath(string $binary, string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        Storage::disk('public')->makeDirectory(dirname($relativePath));
        Storage::disk('public')->put($relativePath, $binary);

        return Storage::disk('public')->exists($relativePath) ? $relativePath : null;
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }
}
