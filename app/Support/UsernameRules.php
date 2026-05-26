<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsernameRules
{
    public const PATTERN = '/^[a-z0-9_-]{3,30}$/';

    public static function normalize(?string $username): string
    {
        $username = Str::lower(trim((string) $username));
        $username = str_replace(' ', '-', $username);
        $username = preg_replace('/[^a-z0-9_-]/', '', $username) ?? '';

        return $username;
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(?int $ignoreUserId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:'.self::PATTERN,
            Rule::unique('users', 'username')->ignore($ignoreUserId),
        ];
    }
}
