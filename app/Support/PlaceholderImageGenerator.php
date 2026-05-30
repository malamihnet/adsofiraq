<?php

namespace App\Support;

use RuntimeException;

class PlaceholderImageGenerator
{
    private const BACKGROUND = [230, 230, 230];

    private const TEXT_COLOR = [143, 143, 143];

    /**
     * @var array<string, array{width: int, height: int, lines: list<string>, fontSize: int}>
     */
    private const SPECS = [
        Placeholder::TYPE_SQUARE => [
            'width' => 500,
            'height' => 500,
            'lines' => ['ADS OF IRAQ'],
            'fontSize' => 28,
        ],
        Placeholder::TYPE_LANDSCAPE => [
            'width' => 1200,
            'height' => 675,
            'lines' => ['ADS OF IRAQ'],
            'fontSize' => 42,
        ],
        Placeholder::TYPE_PORTRAIT => [
            'width' => 800,
            'height' => 1200,
            'lines' => ['ADS OF', 'IRAQ'],
            'fontSize' => 36,
        ],
    ];

    /**
     * @return list<string> Absolute paths of generated JPG files.
     */
    public function generateAll(?string $outputDirectory = null): array
    {
        $outputDirectory ??= public_path('placeholders');

        if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0755, true) && ! is_dir($outputDirectory)) {
            throw new RuntimeException("Could not create directory: {$outputDirectory}");
        }

        $fontPath = $this->resolveFontPath();
        $paths = [];

        foreach (self::SPECS as $type => $spec) {
            $paths[] = $this->generateType($type, $spec, $outputDirectory, $fontPath);
        }

        return $paths;
    }

    /**
     * @param  array{width: int, height: int, lines: list<string>, fontSize: int}  $spec
     */
    public function generateType(string $type, array $spec, string $outputDirectory, ?string $fontPath = null): string
    {
        $fontPath ??= $this->resolveFontPath();
        $image = imagecreatetruecolor($spec['width'], $spec['height']);

        if ($image === false) {
            throw new RuntimeException('Could not allocate image buffer.');
        }

        $background = imagecolorallocate($image, ...self::BACKGROUND);
        $textColor = imagecolorallocate($image, ...self::TEXT_COLOR);
        imagefilledrectangle($image, 0, 0, $spec['width'], $spec['height'], $background);

        $this->drawCenteredLines($image, $spec['lines'], $spec['fontSize'], $textColor, $spec['width'], $spec['height'], $fontPath);

        $filename = 'placeholder-'.Placeholder::normalizeType($type).'.jpg';
        $path = rtrim($outputDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

        if (! imagejpeg($image, $path, 90)) {
            imagedestroy($image);

            throw new RuntimeException("Could not write JPEG: {$path}");
        }

        imagedestroy($image);

        return $path;
    }

    /**
     * @param  list<string>  $lines
     */
    private function drawCenteredLines(
        \GdImage $image,
        array $lines,
        int $fontSize,
        int $textColor,
        int $width,
        int $height,
        ?string $fontPath,
    ): void {
        if ($fontPath !== null && function_exists('imagettfbbox')) {
            $lineHeight = (int) round($fontSize * 1.35);
            $blockHeight = count($lines) * $lineHeight;
            $y = (int) round(($height - $blockHeight) / 2 + $fontSize);

            foreach ($lines as $line) {
                $box = imagettfbbox($fontSize, 0, $fontPath, $line);
                $lineWidth = abs($box[2] - $box[0]);
                $x = (int) round(($width - $lineWidth) / 2);
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $line);
                $y += $lineHeight;
            }

            return;
        }

        $font = 5;
        $lineHeight = imagefontheight($font) + 8;
        $blockHeight = count($lines) * $lineHeight;
        $y = (int) round(($height - $blockHeight) / 2);

        foreach ($lines as $line) {
            $lineWidth = imagefontwidth($font) * strlen($line);
            $x = (int) round(($width - $lineWidth) / 2);
            imagestring($image, $font, max(0, $x), $y, $line, $textColor);
            $y += $lineHeight;
        }
    }

    public function resolveFontPath(): ?string
    {
        $candidates = array_filter([
            resource_path('fonts/placeholder.ttf'),
            resource_path('fonts/Inter-Light.ttf'),
            'C:\\Windows\\Fonts\\segoeuil.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
        ]);

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
