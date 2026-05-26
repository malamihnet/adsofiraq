<?php

namespace App\Services\Import;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class CampaignImportMediaOptimizer
{
    protected ?ImageManager $imageManager = null;

    public function storeImageAsWebp(
        string $binary,
        Campaign $campaign,
        string $directory = 'campaigns/assets',
    ): ?string {
        Storage::disk('public')->makeDirectory($directory);

        $maxWidth = (int) config('import.image_max_width', 2560);
        $quality = (int) config('import.webp_quality', 82);

        try {
            $image = $this->imageManager()->decodeBinary($binary);
            $width = $image->width();
            $height = $image->height();

            if ($width > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            $filename = sprintf('import-c%d-%s.webp', $campaign->id, Str::random(12));
            $path = $directory.'/'.$filename;

            Storage::disk('public')->put(
                $path,
                (string) $image->encode(new WebpEncoder(quality: $quality))
            );

            if (Storage::disk('public')->missing($path)) {
                return null;
            }

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Campaign import: image WebP conversion failed.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }
}
