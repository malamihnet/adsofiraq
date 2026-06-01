<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonPhotoService
{
    public const DIRECTORY = 'people/avatars';

    public function __construct(
        protected PublicStorageSyncService $publicStorageSync,
    ) {}

    public function store(UploadedFile $file): string
    {
        $path = $this->storeUploadedFile($file);
        $this->publicStorageSync->syncRelativePath($path);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $normalized = $this->normalizePath($path);

        if ($normalized && Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }

    public function replace(?string $oldPath, UploadedFile $file): string
    {
        $this->delete($oldPath);

        return $this->store($file);
    }

    protected function storeUploadedFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs(self::DIRECTORY, $filename, 'public');
    }

    protected function normalizePath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        return $path !== '' ? $path : null;
    }
}
