<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;
use Throwable;

class ViteBuildDiagnostics
{
    /**
     * @return array{
     *     manifest_path: string,
     *     manifest_app_file: ?string,
     *     manifest_asset_exists: bool,
     *     vite_asset_url: ?string,
     *     vite_asset_error: ?string,
     *     vite_matches_manifest: bool,
     *     public_path: string,
     *     directory_checks: array<string, array{
     *         path: string,
     *         exists: bool,
     *         manifest_readable: bool,
     *         manifest_app_js: ?string,
     *         asset_file_exists: ?bool
     *     }>
     * }
     */
    public static function collect(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $manifestAppFile = null;

        if (is_readable($manifestPath)) {
            $manifestData = json_decode((string) file_get_contents($manifestPath), true);
            $manifestAppFile = $manifestData['resources/js/app.js']['file'] ?? null;
        }

        $viteAsset = null;
        $viteAssetError = null;

        try {
            $viteAsset = Vite::asset('resources/js/app.js');
        } catch (Throwable $exception) {
            $viteAssetError = $exception->getMessage();
        }

        $directoryChecks = [];

        foreach (self::candidateBuildDirectories() as $label => $directory) {
            $directoryChecks[$label] = self::inspectBuildDirectory($directory);
        }

        $manifestAssetExists = $manifestAppFile !== null
            && is_file(public_path('build/'.$manifestAppFile));

        $viteBasename = $viteAsset !== null
            ? basename((string) parse_url($viteAsset, PHP_URL_PATH))
            : null;
        $manifestBasename = $manifestAppFile !== null ? basename($manifestAppFile) : null;

        return [
            'manifest_path' => $manifestPath,
            'manifest_app_file' => $manifestAppFile,
            'manifest_asset_exists' => $manifestAssetExists,
            'vite_asset_url' => $viteAsset,
            'vite_asset_error' => $viteAssetError,
            'vite_matches_manifest' => $viteBasename !== null
                && $manifestBasename !== null
                && $viteBasename === $manifestBasename,
            'public_path' => public_path(),
            'directory_checks' => $directoryChecks,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function candidateBuildDirectories(): array
    {
        return [
            'public_path(build)' => public_path('build'),
            'base_path(public/build)' => base_path('public/build'),
            'base_path(public_html/build)' => base_path('public_html/build'),
            'base_path(public_html/public/build)' => base_path('public_html/public/build'),
        ];
    }

    /**
     * @return array{
     *     path: string,
     *     exists: bool,
     *     manifest_readable: bool,
     *     manifest_app_js: ?string,
     *     asset_file_exists: ?bool
     * }
     */
    public static function inspectBuildDirectory(string $directory): array
    {
        $manifestFile = $directory.DIRECTORY_SEPARATOR.'manifest.json';
        $info = [
            'path' => $directory,
            'exists' => is_dir($directory),
            'manifest_readable' => is_readable($manifestFile),
            'manifest_app_js' => null,
            'asset_file_exists' => null,
        ];

        if (! is_readable($manifestFile)) {
            return $info;
        }

        $manifestData = json_decode((string) file_get_contents($manifestFile), true);
        $appFile = $manifestData['resources/js/app.js']['file'] ?? null;
        $info['manifest_app_js'] = $appFile;
        $info['asset_file_exists'] = $appFile !== null
            ? is_file($directory.DIRECTORY_SEPARATOR.$appFile)
            : false;

        return $info;
    }
}
