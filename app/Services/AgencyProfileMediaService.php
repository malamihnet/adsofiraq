<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AgencyProfileMediaService
{
    public function storeLogo(UploadedFile $file): string
    {
        return $file->store('agencies/logos', 'public');
    }

    public function storeCover(UploadedFile $file): string
    {
        return $file->store('agencies/covers', 'public');
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function replaceLogo(?string $oldPath, UploadedFile $file): string
    {
        $this->delete($oldPath);

        return $this->storeLogo($file);
    }

    public function replaceCover(?string $oldPath, UploadedFile $file): string
    {
        $this->delete($oldPath);

        return $this->storeCover($file);
    }
}
