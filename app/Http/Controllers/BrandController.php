<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::withCount(['campaigns' => fn ($q) => $q->approved()])
            ->orderByDesc('campaigns_count')
            ->paginate(48);

        return view('brands.index', compact('brands'));
    }

    public function show(Brand $brand): View
    {
        $campaigns = $brand->campaigns()
            ->public()
            ->with(['agencies', 'brands', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        return view('brands.show', compact('brand', 'campaigns'));
    }
}
