<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlatformVerificationRequest;
use App\Models\Agency;
use App\Models\Brand;
use App\Services\PlatformVerificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    protected array $models = [
        'brands' => Brand::class,
        'agencies' => Agency::class,
        'industries' => \App\Models\Industry::class,
        'medium-types' => \App\Models\MediumType::class,
        'countries' => \App\Models\Country::class,
    ];

    protected array $verifiableTypes = ['brands', 'agencies'];

    protected array $labels = [
        'brands' => 'Brands',
        'agencies' => 'Agencies',
        'industries' => 'Industries',
        'medium-types' => 'Medium Types',
        'countries' => 'Countries',
    ];

    public function __construct(
        protected PlatformVerificationService $verificationService,
    ) {}

    public function index(string $type): View
    {
        $model = $this->getModel($type);
        $query = $model::orderBy('name');

        if ($this->isVerifiable($type)) {
            $query->platformVerificationFilter(request()->input('verified'));
        }

        return view('admin.taxonomy.index', [
            'type' => $type,
            'label' => $this->labels[$type],
            'verifiable' => $this->isVerifiable($type),
            'items' => $query->paginate(50)->withQueryString(),
        ]);
    }

    public function agencies(): View
    {
        return $this->index('agencies');
    }

    public function brands(): View
    {
        return $this->index('brands');
    }

    public function industries(): View
    {
        return $this->index('industries');
    }

    public function mediumTypes(): View
    {
        return $this->index('medium-types');
    }

    public function countries(): View
    {
        return $this->index('countries');
    }

    public function show(string $type, int $id): View
    {
        if (! $this->isVerifiable($type)) {
            abort(404);
        }

        $model = $this->getModel($type);
        $item = $model::with(['verifiedBy'])->withCount('campaigns')->findOrFail($id);

        return view('admin.taxonomy.show', [
            'type' => $type,
            'label' => $this->labels[$type],
            'item' => $item,
        ]);
    }

    public function showAgency(int $id): View
    {
        return $this->show('agencies', $id);
    }

    public function showBrand(int $id): View
    {
        return $this->show('brands', $id);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $model = $this->getModel($type);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $model::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Item created.');
    }

    public function storeAgencies(Request $request): RedirectResponse
    {
        return $this->store($request, 'agencies');
    }

    public function storeBrands(Request $request): RedirectResponse
    {
        return $this->store($request, 'brands');
    }

    public function storeIndustries(Request $request): RedirectResponse
    {
        return $this->store($request, 'industries');
    }

    public function storeMediumTypes(Request $request): RedirectResponse
    {
        return $this->store($request, 'medium-types');
    }

    public function storeCountries(Request $request): RedirectResponse
    {
        return $this->store($request, 'countries');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->getModel($type);
        $item = $model::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if ($type === 'agencies') {
            $rules['is_production_house'] = ['sometimes', 'boolean'];
        }

        $validated = $request->validate($rules);

        $payload = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ];

        if ($type === 'agencies') {
            $payload['is_production_house'] = $request->boolean('is_production_house');
        }

        $item->update($payload);

        return back()->with('success', 'Item updated.');
    }

    public function updateAgency(Request $request, int $id): RedirectResponse
    {
        return $this->update($request, 'agencies', $id);
    }

    public function updateBrand(Request $request, int $id): RedirectResponse
    {
        return $this->update($request, 'brands', $id);
    }

    public function updateIndustry(Request $request, int $id): RedirectResponse
    {
        return $this->update($request, 'industries', $id);
    }

    public function updateMediumType(Request $request, int $id): RedirectResponse
    {
        return $this->update($request, 'medium-types', $id);
    }

    public function updateCountry(Request $request, int $id): RedirectResponse
    {
        return $this->update($request, 'countries', $id);
    }

    public function updateVerification(UpdatePlatformVerificationRequest $request, string $type, int $id): RedirectResponse
    {
        if (! $this->isVerifiable($type)) {
            abort(404);
        }

        $model = $this->getModel($type);
        $item = $model::findOrFail($id);

        $this->verificationService->update(
            $item,
            $request->user(),
            $request->boolean('is_verified')
        );

        $label = rtrim($this->labels[$type], 's');

        return back()->with('success', $request->boolean('is_verified')
            ? "{$label} verified by Ads of Iraq."
            : "{$label} verification removed.");
    }

    public function verifyAgency(UpdatePlatformVerificationRequest $request, int $id): RedirectResponse
    {
        return $this->updateVerification($request, 'agencies', $id);
    }

    public function verifyBrand(UpdatePlatformVerificationRequest $request, int $id): RedirectResponse
    {
        return $this->updateVerification($request, 'brands', $id);
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = $this->getModel($type);
        $item = $model::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Item deleted.');
    }

    public function destroyAgency(int $id): RedirectResponse
    {
        return $this->destroy('agencies', $id);
    }

    public function destroyBrand(int $id): RedirectResponse
    {
        return $this->destroy('brands', $id);
    }

    public function destroyIndustry(int $id): RedirectResponse
    {
        return $this->destroy('industries', $id);
    }

    public function destroyMediumType(int $id): RedirectResponse
    {
        return $this->destroy('medium-types', $id);
    }

    public function destroyCountry(int $id): RedirectResponse
    {
        return $this->destroy('countries', $id);
    }

    protected function getModel(string $type): string
    {
        if (! isset($this->models[$type])) {
            abort(404);
        }

        return $this->models[$type];
    }

    protected function isVerifiable(string $type): bool
    {
        return in_array($type, $this->verifiableTypes, true);
    }
}
