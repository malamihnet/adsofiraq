<?php

namespace App\Console\Commands;

use App\Support\PlaceholderImageGenerator;
use Illuminate\Console\Command;

class GeneratePlaceholdersCommand extends Command
{
    protected $signature = 'placeholders:generate';

    protected $description = 'Generate branded local placeholder JPG files in public/placeholders';

    public function handle(PlaceholderImageGenerator $generator): int
    {
        $font = $generator->resolveFontPath();

        if ($font === null) {
            $this->warn('No TrueType font found; using built-in bitmap font (lower quality).');
            $this->line('Add resources/fonts/placeholder.ttf for consistent typography.');
        } else {
            $this->line("Using font: {$font}");
        }

        $paths = $generator->generateAll();

        foreach ($paths as $path) {
            $this->info('Wrote '.basename($path));
        }

        $this->newLine();
        $this->info('Branded placeholders are ready in public/placeholders/.');

        return self::SUCCESS;
    }
}
