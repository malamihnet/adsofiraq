<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AgencyProfileMediaService
{
    public function __construct(
        protected PublicStorageSyncService $publicStorageSync,
    ) {}

    public function storeLogo(UploadedFile $file): string
    {
        $path = $file->store('agencies/logos', 'public');
        $this->publicStorageSync->syncRelativePath($path);

        return $path;
    }

    public function storeCover(UploadedFile $file): string
    {
        $path = $file->store('agencies/covers', 'public');
        $this->publicStorageSync->syncRelativePath($path);

        return $path;
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
