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
use App\Services\SeoService;
use App\Services\CreditsMentionService;
use App\Services\CampaignTagService;
use App\Services\StructuredDataService;
use App\Services\CampaignRevisionUploadService;
use App\Services\CampaignTaxonomySyncService;
use App\Services\CampaignUploadService;
use App\Services\CampaignVideoService;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        protected SeoService $seo,
        protected CampaignPeopleCreditService $peopleCredits,
        protected CreditsMentionService $creditsMentions,
        protected CampaignTagService $tagService,
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
        $usePlacementOrdering = $sort === 'latest';

        if ($sort === 'views') {
            $query->orderByDesc('views_count');
            $usePlacementOrdering = false;
        } elseif ($sort === 'bookmarks') {
            $query->orderByDesc('bookmarks_count');
            $usePlacementOrdering = false;
        } elseif ($sort === 'oldest') {
            $query->orderBy('approved_at')->orderBy('id');
            $usePlacementOrdering = false;
        }

        $eagerLoads = ['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries'];

        $perPage = $this->resolveArchivePerPage($request);

        $campaigns = $this->archiveOrdering->paginate(
            $query,
            perPage: $perPage,
            usePlacementOrdering: $usePlacementOrdering,
            eagerLoads: $eagerLoads,
        );

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Campaigns', 'url' => route('campaigns.index')],
            ]),
        ];

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'perPage' => $perPage,
            'schema' => $schema,
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

    public function editorsPick(): View
    {
        $campaigns = Campaign::public()
            ->editorsPick()
            ->with(['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries'])
            ->latestOnPlatform()
            ->paginate(24)
            ->withQueryString();

        return view('campaigns.editors-pick', [
            'campaigns' => $campaigns,
        ]);
    }

    public function show(Campaign $campaign): View|RedirectResponse|Response
    {
        if ($campaign->status !== 'approved') {
            $user = auth()->user();

            if ($user && $user->can('viewPendingReview', $campaign)) {
                return $this->redirectToPendingReview($campaign, 'not_live_yet');
            }

            if ($user) {
                return $this->campaignAccessDenied($campaign);
            }

            abort(404);
        }

        $this->authorize('view', $campaign);

        if ($campaign->status === 'approved') {
            $campaign->increment('views_count');
        }

        $campaign->load(['brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries', 'assets', 'videos', 'people', 'tags']);

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

        $seo = $this->seo->forCampaign($campaign, $canonicalUrl);
        $creditsHtml = $this->creditsMentions->renderCreditsHtml($campaign);

        return view('campaigns.show', compact(
            'campaign',
            'relatedGroups',
            'isBookmarked',
            'isWatched',
            'canonicalUrl',
            'schema',
            'seo',
            'creditsHtml',
        ));
    }

    public function pendingReview(Campaign $campaign): View|RedirectResponse|Response
    {
        $user = auth()->user();

        if (! $user || ! $user->can('viewPendingReview', $campaign)) {
            return $this->campaignAccessDenied($campaign);
        }

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

            $this->syncCreditMentions($campaign, $request);

            Log::info('Campaign created', ['campaign_id' => $campaign->id, 'user_id' => $request->user()->id]);

            return $this->redirectToPendingReview($campaign->fresh(), 'submitted');
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
            ['campaign' => $campaign->load(['assets', 'videos', 'agencies', 'productionHouses', 'brands', 'industries', 'mediumTypes', 'countries', 'pendingRevision', 'people'])],
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
                'credit_mentions' => $request->input('credit_mentions'),
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

            return $this->redirectToPendingReview($campaign, 'updated');
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

        $this->syncCreditMentions($campaign, $request);

        if ($campaign->status !== 'approved') {
            return $this->redirectToPendingReview($campaign->fresh(), 'updated');
        }

        return redirect()->route('campaigns.show', $campaign->fresh())
            ->with('success', 'Campaign updated successfully.');
    }

    protected function redirectToPendingReview(Campaign $campaign, string $notice = 'updated'): RedirectResponse
    {
        return redirect()
            ->route('campaigns.pending-review', $campaign)
            ->with('pending_review_notice', $notice);
    }

    protected function campaignAccessDenied(?Campaign $campaign = null): Response
    {
        return response()->view('campaigns.access-denied', [
            'campaign' => $campaign,
        ], Response::HTTP_FORBIDDEN);
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
            'creditMentions' => $this->creditMentionsForForm($campaign),
        ];
    }

    /**
     * @return array<int, array{person_id: int, role: string, name: string, slug: string, photo_url: string}>
     */
    protected function creditMentionsForForm(?Campaign $campaign): array
    {
        if (old('credit_mentions') !== null) {
            return $this->creditsMentions->hydrateMentionsForForm(
                $this->creditsMentions->decodeMentionsInput(old('credit_mentions')),
            );
        }

        return $campaign
            ? $this->creditsMentions->mentionsForForm($campaign)
            : [];
    }

    protected function syncCreditMentions(Campaign $campaign, Request $request): void
    {
        $mentions = $this->creditsMentions->decodeMentionsInput($request->input('credit_mentions'));
        $this->creditsMentions->syncFromCredits($campaign, $request->input('credits'), $mentions);
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
