<?php

namespace App\Support;

use InvalidArgumentException;

class Placeholder
{
    public const TYPE_SQUARE = 'square';

    public const TYPE_LANDSCAPE = 'landscape';

    public const TYPE_PORTRAIT = 'portrait';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_SQUARE,
        self::TYPE_LANDSCAPE,
        self::TYPE_PORTRAIT,
    ];

    public static function url(string $type = self::TYPE_LANDSCAPE): string
    {
        return asset(self::publicRelativePath(self::normalizeType($type)));
    }

    public static function publicRelativePath(string $type): string
    {
        $type = self::normalizeType($type);

        return "placeholders/placeholder-{$type}.jpg";
    }

    public static function absolutePath(string $type): string
    {
        return public_path(self::publicRelativePath($type));
    }

    public static function normalizeType(string $type): string
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown placeholder type [{$type}]. Expected: ".implode(', ', self::TYPES));
        }

        return $type;
    }
}
