<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesArchivePerPage
{
    protected function resolveArchivePerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 24);

        return in_array($perPage, [24, 50, 100], true) ? $perPage : 24;
    }
}
