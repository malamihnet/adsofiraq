<?php

use App\Support\Placeholder;

if (! function_exists('placeholderUrl')) {
    /**
     * Public URL for a branded local placeholder image.
     *
     * @param  'square'|'landscape'|'portrait'  $type
     */
    function placeholderUrl(string $type): string
    {
        return Placeholder::url($type);
    }
}
