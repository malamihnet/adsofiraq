<?php

namespace App\Services\Import;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignImportVideoConverter
{
    public function convertToWebm(Campaign $campaign, string $sourceRelativePath): ?string
    {
        if (! $this->isFfmpegAvailable()) {
            Log::info('Campaign import: FFmpeg unavailable, keeping original video.', [
                'campaign_id' => $campaign->id,
                'path' => $sourceRelativePath,
            ]);

            return $sourceRelativePath;
        }

        $disk = Storage::disk('public');
        $inputPath = $disk->path($sourceRelativePath);

        if (! is_file($inputPath)) {
            return $sourceRelativePath;
        }

        $outputRelative = sprintf(
            'campaigns/videos/import-c%d-%s.webm',
            $campaign->id,
            Str::random(12)
        );

        $disk->makeDirectory('campaigns/videos');
        $outputPath = $disk->path($outputRelative);

        $crf = (int) config('import.video_webm_crf', 32);
        $timeout = (int) config('import.ffmpeg_timeout', 120);

        $command = sprintf(
            '%s -y -i %s -c:v libvpx-vp9 -crf %d -b:v 0 -row-mt 1 -threads 2 -an -deadline good %s 2>&1',
            escapeshellarg($this->ffmpegPath()),
            escapeshellarg($inputPath),
            $crf,
            escapeshellarg($outputPath)
        );

        try {
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
        } catch (\Throwable $e) {
            Log::warning('Campaign import: video conversion failed (exception), using original.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return $sourceRelativePath;
        }

        if ($returnCode !== 0 || ! is_file($outputPath) || filesize($outputPath) === 0) {
            Log::warning('Campaign import: video conversion failed, using original.', [
                'campaign_id' => $campaign->id,
                'return_code' => $returnCode,
                'output' => implode("\n", array_slice($output, -8)),
            ]);

            if (is_file($outputPath)) {
                @unlink($outputPath);
            }

            return $sourceRelativePath;
        }

        if ($sourceRelativePath !== $outputRelative && $disk->exists($sourceRelativePath)) {
            $disk->delete($sourceRelativePath);
        }

        Log::info('Campaign import: video converted to WebM.', [
            'campaign_id' => $campaign->id,
            'path' => $outputRelative,
        ]);

        return $outputRelative;
    }

    public function isFfmpegAvailable(): bool
    {
        if (! $this->isExecAvailable()) {
            return false;
        }

        $command = sprintf('%s -version 2>&1', escapeshellarg($this->ffmpegPath()));

        try {
            exec($command, $output, $returnCode);
        } catch (\Throwable) {
            return false;
        }

        return $returnCode === 0;
    }

    protected function isExecAvailable(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions');

        if (! is_string($disabled) || $disabled === '') {
            return true;
        }

        return ! in_array('exec', array_map('trim', explode(',', strtolower($disabled))), true);
    }

    protected function ffmpegPath(): string
    {
        $path = config('import.ffmpeg_path', config('upload.ffmpeg_path', 'ffmpeg'));

        return is_string($path) && trim($path) !== '' ? trim($path) : 'ffmpeg';
    }
}
