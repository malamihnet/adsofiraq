<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarService
{
    public function store(UploadedFile $file): string
    {
        return $file->store('avatars', 'public');
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

    public function replace(?string $oldPath, UploadedFile $file): string
    {
        $this->delete($oldPath);

        return $this->store($file);
    }
}
