<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use App\Services\CampaignTaxonomySyncService;
use App\Services\CampaignUploadService;
use App\Services\CampaignVideoService;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        protected TaxonomyService $taxonomyService,
        protected CampaignUploadService $uploadService,
        protected CampaignVideoService $videoService,
        protected CampaignTaxonomySyncService $taxonomySyncService,
    ) {}

    public function index(Request $request): View
    {
        $query = Campaign::public()
            ->with(['brands', 'agencies', 'industries', 'mediumTypes', 'countries']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('brands', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('agencies', fn ($a) => $a->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('brand')) {
            $query->whereHas('brands', fn ($q) => $q->where('slug', $request->brand));
        }

        if ($request->filled('agency')) {
            $query->whereHas('agencies', fn ($q) => $q->where('slug', $request->agency));
        }

        if ($request->filled('industry')) {
            $query->whereHas('industries', fn ($q) => $q->where('slug', $request->industry));
        }

        if ($request->filled('medium')) {
            $query->whereHas('mediumTypes', fn ($q) => $q->where('slug', $request->medium));
        }

        if ($request->filled('country')) {
            $query->whereHas('countries', fn ($q) => $q->where('slug', $request->country));
        }

        if ($request->filled('year')) {
            $query->whereYear('published_at', $request->year);
        }

        match ($request->get('sort', 'latest')) {
            'views' => $query->orderByDesc('views_count'),
            'bookmarks' => $query->orderByDesc('bookmarks_count'),
            default => $query->latestOnPlatform(),
        };

        $campaigns = $query->paginate(24)->withQueryString();

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'brands' => Brand::orderBy('name')->get(),
            'agencies' => Agency::orderBy('name')->get(),
            'industries' => Industry::orderBy('name')->get(),
            'mediumTypes' => MediumType::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'years' => Campaign::public()
                ->whereNotNull('published_at')
                ->selectRaw('YEAR(published_at) as year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }

    public function show(Campaign $campaign): View
    {
        if ($campaign->status !== 'approved') {
            $user = auth()->user();
            if (! $user || (! $user->isAdmin() && $user->id !== $campaign->user_id)) {
                abort(404);
            }
        }

        $this->authorize('view', $campaign);

        if ($campaign->status === 'approved') {
            $campaign->increment('views_count');
        }

        $campaign->load(['brands', 'agencies', 'industries', 'mediumTypes', 'countries', 'assets', 'videos']);

        $relatedCampaigns = Campaign::public()
            ->where('id', '!=', $campaign->id)
            ->when($campaign->brands->isNotEmpty(), fn ($q) => $q->whereHas('brands', fn ($b) => $b->whereIn('brands.id', $campaign->brands->pluck('id'))))
            ->when($campaign->brands->isEmpty() && $campaign->industries->isNotEmpty(), fn ($q) => $q->whereHas('industries', fn ($i) => $i->whereIn('industries.id', $campaign->industries->pluck('id'))))
            ->with(['brands', 'agencies'])
            ->take(4)
            ->get();

        $user = auth()->user();
        $isBookmarked = $user ? $campaign->isBookmarkedBy($user) : false;
        $isWatched = $user ? $campaign->isWatchedBy($user) : false;

        return view('campaigns.show', compact('campaign', 'relatedCampaigns', 'isBookmarked', 'isWatched'));
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('campaigns.create', $this->formData());
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        Log::info('Campaign submit started', ['user_id' => $request->user()->id]);

        try {
            $campaign = Campaign::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'published_at' => $request->published_at,
                'description' => $request->description ?? '',
                'credits' => $request->credits,
                'status' => 'pending',
                'is_student' => $request->boolean('is_student'),
                'is_nsfw' => $request->boolean('is_nsfw'),
                'submission_notes' => $request->submission_notes,
            ]);

            $this->taxonomySyncService->syncAll(
                $campaign,
                agencies: $request->input('agencies', []),
                brands: $request->input('brands', []),
                industries: $request->input('industries', []),
                mediumTypes: $request->input('medium_types', []),
                countries: $request->input('countries', []),
            );

            $this->videoService->syncFromRequest($campaign, $request);

            $manualThumbnail = $request->hasFile('thumbnail');

            if ($manualThumbnail) {
                $campaign->update([
                    'thumbnail_path' => $this->uploadService->storeThumbnail($campaign, $request->file('thumbnail')),
                ]);
            }

            $firstNewAsset = $request->hasFile('assets')
                ? $this->uploadService->storeAssets($campaign, $request->file('assets'))
                : null;

            $this->uploadService->resolveThumbnail($campaign->fresh(), $manualThumbnail, $firstNewAsset);

            Log::info('Campaign created', ['campaign_id' => $campaign->id, 'user_id' => $request->user()->id]);

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign submitted successfully and is pending review.');
        } catch (\Throwable $e) {
            report($e);

            Log::warning('Campaign submit failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => 'We could not save your campaign. Please check your files and try again.',
                ]);
        }
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        return view('campaigns.edit', array_merge(
            ['campaign' => $campaign->load(['assets', 'videos', 'agencies', 'brands', 'industries', 'mediumTypes', 'countries'])],
            $this->formData($campaign)
        ));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $data = [
            'title' => $request->title,
            'published_at' => $request->published_at,
            'description' => $request->description,
            'credits' => $request->credits,
            'is_student' => $request->boolean('is_student'),
            'is_nsfw' => $request->boolean('is_nsfw'),
            'submission_notes' => $request->submission_notes,
        ];

        if ($request->user()->isAdmin()) {
            if ($request->has('status')) {
                $data['status'] = $request->status;
            }
            if ($request->has('is_featured')) {
                $data['is_featured'] = $request->boolean('is_featured');
            }
            if ($request->has('admin_notes')) {
                $data['admin_notes'] = $request->admin_notes;
            }
        } elseif ($campaign->status === 'rejected') {
            $data['status'] = 'pending';
        }

        $manualThumbnail = $request->hasFile('thumbnail');

        if ($manualThumbnail) {
            $data['thumbnail_path'] = $this->uploadService->storeThumbnail($campaign, $request->file('thumbnail'));
        }

        $campaign->update($data);

        $this->taxonomySyncService->syncAll(
            $campaign,
            agencies: $request->input('agencies', []),
            brands: $request->input('brands', []),
            industries: $request->input('industries', []),
            mediumTypes: $request->input('medium_types', []),
            countries: $request->input('countries', []),
        );

        $this->videoService->syncFromRequest($campaign, $request);

        $firstNewAsset = $request->hasFile('assets')
            ? $this->uploadService->storeAssets($campaign, $request->file('assets'))
            : null;

        $this->uploadService->resolveThumbnail($campaign->fresh(), $manualThumbnail, $firstNewAsset);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    protected function formData(?Campaign $campaign = null): array
    {
        $selected = $this->taxonomySelections($campaign);

        return [
            'industries' => Industry::orderBy('name')->get(),
            'mediumTypes' => MediumType::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'agencies' => Agency::orderBy('name')->get(),
            'selectedTaxonomies' => $selected,
        ];
    }

  /**
     * @return array<string, array<int, array{id: int|null, name: string}>>
     */
    protected function taxonomySelections(?Campaign $campaign): array
    {
        if ($this->hasOldTaxonomyInput()) {
            return $this->taxonomySyncService->oldInputSelections();
        }

        if ($campaign) {
            $campaign->loadMissing(['agencies', 'brands', 'industries', 'mediumTypes', 'countries']);

            return $this->taxonomySyncService->selectedForForm($campaign);
        }

        return [
            'agencies' => [],
            'brands' => [],
            'industries' => [],
            'medium_types' => [],
            'countries' => [],
        ];
    }

    protected function hasOldTaxonomyInput(): bool
    {
        foreach (['agencies', 'brands', 'industries', 'medium_types', 'countries'] as $field) {
            if (old($field) !== null) {
                return true;
            }
        }

        return false;
    }

}
