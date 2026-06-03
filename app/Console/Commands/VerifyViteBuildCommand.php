<?php

namespace App\Console\Commands;

use App\Support\ViteBuildDiagnostics;
use Illuminate\Console\Command;

class VerifyViteBuildCommand extends Command
{
    protected $signature = 'vite:verify-build';

    protected $description = 'Verify Vite manifest and built assets exist where Laravel expects them';

    public function handle(): int
    {
        $diagnostics = ViteBuildDiagnostics::collect();

        $this->info('Manifest path: '.$diagnostics['manifest_path']);
        $this->info('Manifest app.js: '.($diagnostics['manifest_app_file'] ?? 'missing'));
        $this->info('Manifest asset on disk: '.($diagnostics['manifest_asset_exists'] ? 'yes' : 'no'));

        if ($diagnostics['vite_asset_error']) {
            $this->error('Vite::asset error: '.$diagnostics['vite_asset_error']);
        } else {
            $this->info('Vite::asset: '.$diagnostics['vite_asset_url']);
        }

        $this->newLine();
        $this->info('Build directories:');

        foreach ($diagnostics['directory_checks'] as $label => $check) {
            $status = ! $check['exists']
                ? 'missing dir'
                : (! $check['manifest_readable']
                    ? 'no manifest'
                    : ($check['asset_file_exists'] ? 'ok' : 'asset missing'));

            $this->line("  {$label}: {$status}");

            if ($check['manifest_app_js']) {
                $this->line("    manifest app.js: {$check['manifest_app_js']}");
            }
        }

        if (! $diagnostics['manifest_asset_exists']) {
            $this->newLine();
            $this->error('Laravel public_path build is incomplete. Run npm run build and deploy public/build.');
            $this->line('On cPanel with public_html docroot, also copy public/build to public_html/build.');

            return self::FAILURE;
        }

        $htmlBuild = $diagnostics['directory_checks']['base_path(public_html/build)'] ?? null;

        if ($htmlBuild && $htmlBuild['exists'] && $htmlBuild['manifest_readable']) {
            $laravelFile = $diagnostics['manifest_app_file'];
            $htmlFile = $htmlBuild['manifest_app_js'];

            if ($laravelFile !== $htmlFile) {
                $this->newLine();
                $this->warn("public_html/build manifest differs from Laravel ({$htmlFile} vs {$laravelFile}).");
                $this->line('Sync: cp -r public/build/* public_html/build/');
            }
        }

        return self::SUCCESS;
    }
}
