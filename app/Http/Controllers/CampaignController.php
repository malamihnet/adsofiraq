<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesArchivePerPage;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\CampaignRevision;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use App\Services\CampaignArchiveOrderingService;
use App\Services\CampaignInternalLinksService;
use App\Services\StructuredDataService;
use App\Services\CampaignRevisionUploadService;
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
    use ResolvesArchivePerPage;

    public function __construct(
        protected TaxonomyService $taxonomyService,
        protected CampaignUploadService $uploadService,
        protected CampaignVideoService $videoService,
        protected CampaignTaxonomySyncService $taxonomySyncService,
        protected CampaignRevisionUploadService $revisionUploadService,
        protected CampaignArchiveOrderingService $archiveOrdering,
        protected CampaignInternalLinksService $internalLinks,
        protected StructuredDataService $structuredData,
    ) {}

    public function index(Request $request): View
    {
        $query = Campaign::public()
            ->with(['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('brands', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('agencies', fn ($a) => $a->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('productionHouses', fn ($a) => $a->where('name', 'like', "%{$search}%"));
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

        $sort = $request->get('sort', 'latest');
        $useManualOrdering = $sort === 'latest';

        if ($sort === 'views') {
            $query->orderByDesc('views_count');
            $useManualOrdering = false;
        } elseif ($sort === 'bookmarks') {
            $query->orderByDesc('bookmarks_count');
            $useManualOrdering = false;
        } elseif ($sort === 'oldest') {
            $query->orderBy('approved_at')->orderBy('id');
            $useManualOrdering = false;
        }

        $eagerLoads = ['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries'];

        $perPage = $this->resolveArchivePerPage($request);

        $campaigns = $this->archiveOrdering->paginate(
            $query,
            perPage: $perPage,
            useManualOrdering: $useManualOrdering,
            eagerLoads: $eagerLoads,
        );

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'perPage' => $perPage,
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
            abort(404);
        }

        $this->authorize('view', $campaign);

        if ($campaign->status === 'approved') {
            $campaign->increment('views_count');
        }

        $campaign->load(['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries', 'assets', 'videos']);

        $relatedGroups = $this->internalLinks->groupedRelated($campaign);

        $canonicalUrl = route('campaigns.show', $campaign);
        $schema = array_merge(
            [
                $this->structuredData->breadcrumb([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Campaigns', 'url' => route('campaigns.index')],
                    ['name' => $campaign->title, 'url' => $canonicalUrl],
                ]),
                $this->structuredData->creativeWork($campaign, $canonicalUrl),
            ],
            $this->structuredData->campaignMedia($campaign),
        );

        $user = auth()->user();
        $isBookmarked = $user ? $campaign->isBookmarkedBy($user) : false;
        $isWatched = $user ? $campaign->isWatchedBy($user) : false;

        return view('campaigns.show', compact(
            'campaign',
            'relatedGroups',
            'isBookmarked',
            'isWatched',
            'canonicalUrl',
            'schema',
        ));
    }

    public function pendingReview(Campaign $campaign): View|RedirectResponse
    {
        $user = auth()->user();

        Log::info('Pending review access check', [
            'campaign_id' => $campaign->id,
            'campaign_user_id' => $campaign->user_id ?? null,
            'submitted_by' => $campaign->getAttribute('submitted_by'),
            'created_by' => $campaign->getAttribute('created_by'),
            'auth_id' => auth()->id(),
            'is_admin' => $user?->isAdmin(),
        ]);

        $this->authorize('viewPendingReview', $campaign);

        $campaign->loadMissing('pendingRevision');

        if ($campaign->status === 'approved' && $campaign->pendingRevision === null) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'This campaign is now live on the archive.');
        }

        return view('campaigns.pending-review', [
            'campaign' => $campaign,
        ]);
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
                'user_id' => auth()->id(),
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
                productionHouses: $request->input('production_houses', []),
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

            return redirect()
                ->route('campaigns.pending-review', $campaign->fresh())
                ->with('success', 'Your campaign has been submitted successfully and is pending review.');
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
            ['campaign' => $campaign->load(['assets', 'videos', 'agencies', 'productionHouses', 'brands', 'industries', 'mediumTypes', 'countries', 'pendingRevision'])],
            $this->formData($campaign)
        ));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        if (! $request->user()->isAdmin() && $campaign->status === 'approved') {
            $revision = CampaignRevision::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if (! $revision) {
                $revision = CampaignRevision::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $request->user()->id,
                    'revision_payload' => [],
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);
            } else {
                $revision->update(['submitted_at' => now()]);
            }

            $payload = [
                'title' => $request->title,
                'published_at' => $request->published_at,
                'description' => $request->description,
                'credits' => $request->credits,
                'is_student' => $request->boolean('is_student'),
                'is_nsfw' => $request->boolean('is_nsfw'),
                'submission_notes' => $request->submission_notes,
                'taxonomies' => [
                    'agencies' => $request->input('agencies', []),
                    'production_houses' => $request->input('production_houses', []),
                    'brands' => $request->input('brands', []),
                    'industries' => $request->input('industries', []),
                    'medium_types' => $request->input('medium_types', []),
                    'countries' => $request->input('countries', []),
                ],
                'thumbnail_path' => null,
                'assets_paths' => [],
                'videos' => [],
            ];

            if ($request->hasFile('thumbnail')) {
                $payload['thumbnail_path'] = $this->revisionUploadService->storeThumbnail($revision, $request->file('thumbnail'));
            }

            if ($request->hasFile('assets')) {
                $payload['assets_paths'] = $this->revisionUploadService->storeAssets($revision, $request->file('assets'));
            }

            $payload['videos'] = $this->revisionUploadService->buildVideosPayload($revision, $request);

            $revision->update([
                'revision_payload' => $payload,
            ]);

            return redirect()
                ->route('campaigns.pending-review', $campaign)
                ->with('success', 'Your campaign update has been submitted for review. The currently published version remains live until approval.');
        }

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
            productionHouses: $request->input('production_houses', []),
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

        if (! $request->user()->isAdmin()) {
            return redirect()
                ->route('campaigns.pending-review', $campaign)
                ->with('success', 'Your campaign has been updated and is pending review.');
        }

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
            'productionHouses' => Agency::query()->forProductionHouseSelect()->orderBy('name')->get(),
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
            $campaign->loadMissing(['agencies', 'productionHouses', 'brands', 'industries', 'mediumTypes', 'countries']);

            return $this->taxonomySyncService->selectedForForm($campaign);
        }

        return [
            'agencies' => [],
            'production_houses' => [],
            'brands' => [],
            'industries' => [],
            'medium_types' => [],
            'countries' => [],
        ];
    }

    protected function hasOldTaxonomyInput(): bool
    {
        foreach (['agencies', 'production_houses', 'brands', 'industries', 'medium_types', 'countries'] as $field) {
            if (old($field) !== null) {
                return true;
            }
        }

        return false;
    }

}
