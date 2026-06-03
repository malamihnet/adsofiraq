<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCampaignStoreRequest;
use App\Http\Requests\Admin\UpdateCampaignHeroRequest;
use App\Http\Requests\Admin\UpdatePlatformVerificationRequest;
use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\Industry;
use App\Models\MediumType;
use App\Models\User;
use App\Services\CampaignArchiveOrderingService;
use App\Services\CampaignArchivePlacementService;
use App\Services\CampaignTaxonomySyncService;
use App\Services\CreditsMentionService;
use App\Services\CampaignTagService;
use App\Services\CampaignUploadService;
use App\Mail\CampaignWorkflowMail;
use App\Services\CampaignVideoService;
use App\Services\RankingScoreService;
use Illuminate\Support\Facades\Mail;
use App\Services\PlatformVerificationService;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;
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
        protected PlatformVerificationService $verificationService,
        protected CampaignTaxonomySyncService $taxonomySyncService,
        protected CampaignArchivePlacementService $archivePlacement,
        protected CampaignArchiveOrderingService $archiveOrdering,
        protected CreditsMentionService $creditsMentions,
    ) {}

    public function index(Request $request): View
    {
        $query = Campaign::with(['user', 'brands', 'agencies'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%"));
            });
        }

        if ($request->input('archive_placement') === 'placed') {
            $query->archivePlaced()->orderBy('archive_page')->orderBy('archive_position');
        } elseif ($request->input('archive_placement') === 'auto') {
            $query->archiveAutomatic();
        }

        $query->platformVerificationFilter($request->input('verified'));

        return view('admin.campaigns.index', [
            'campaigns' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.campaigns.create', $this->formData());
    }

    public function store(AdminCampaignStoreRequest $request): RedirectResponse
    {
        $status = $request->input('status');
        $isHero = $request->boolean('is_hero') && $status === 'approved';

        $campaign = Campaign::create([
            'user_id' => $request->input('user_id') ?? $request->user()->id,
            'title' => $request->title,
            'published_at' => $request->published_at ?? ($status === 'approved' ? now() : null),
            'description' => $request->description,
            'credits' => $request->credits,
            'status' => $status,
            'is_featured' => $request->boolean('is_featured'),
            'is_student' => $request->boolean('is_student'),
            'is_nsfw' => $request->boolean('is_nsfw'),
            'is_hero' => $isHero,
            'hero_order' => $isHero ? $request->input('hero_order') : null,
            'submission_notes' => $request->submission_notes,
            'admin_notes' => $request->admin_notes,
            'is_draft' => $request->boolean('is_draft'),
            'needs_changes' => $request->boolean('needs_changes') || $status === 'needs_changes',
            'is_made_by_iraq' => $request->boolean('is_made_by_iraq'),
            'editorial_label' => $request->input('editorial_label') ?: null,
            'ai_summary' => $request->input('ai_summary'),
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

        if ($request->boolean('is_verified')) {
            $this->verificationService->update($campaign, $request->user(), true);
        }

        app(RankingScoreService::class)->refreshCampaign($campaign->fresh());

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function edit(Campaign $campaign): View
    {
        $campaign->load(['assets', 'videos', 'brands', 'agencies', 'productionHouses', 'industries', 'mediumTypes', 'countries', 'people']);

        return view('admin.campaigns.edit', $this->formData($campaign));
    }

    public function update(AdminCampaignStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $status = $request->input('status');
        $isHero = $request->boolean('is_hero') && $status === 'approved';

        $campaign->update([
            'user_id' => $request->input('user_id') ?? $campaign->user_id,
            'title' => $request->title,
            'published_at' => $request->published_at ?? ($status === 'approved' ? ($campaign->published_at ?? now()) : null),
            'description' => $request->description,
            'credits' => $request->credits,
            'status' => $status,
            'is_featured' => $request->boolean('is_featured'),
            'is_student' => $request->boolean('is_student'),
            'is_nsfw' => $request->boolean('is_nsfw'),
            'is_hero' => $isHero,
            'hero_order' => $isHero ? $request->input('hero_order') : null,
            'submission_notes' => $request->submission_notes,
            'admin_notes' => $request->admin_notes,
            'is_draft' => $request->boolean('is_draft'),
            'needs_changes' => $request->boolean('needs_changes') || $status === 'needs_changes',
            'is_made_by_iraq' => $request->boolean('is_made_by_iraq'),
            'editorial_label' => $request->input('editorial_label') ?: null,
            'ai_summary' => $request->input('ai_summary'),
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

        app(RankingScoreService::class)->refreshCampaign($campaign->fresh());

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

        if ($request->boolean('is_verified')) {
            $this->verificationService->update($campaign, $request->user(), true);
        }

        $this->syncArchivePlacementFromRequest($request, $campaign);

        return redirect()
            ->route('admin.campaigns.edit', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load(['user', 'brands', 'agencies', 'industries', 'mediumTypes', 'countries', 'assets', 'videos', 'verifiedBy']);

        return view('admin.campaigns.show', compact('campaign'));
    }

    public function inlineUpdate(Request $request, Campaign $campaign): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['ok' => false, 'message' => 'Admin access required.'], 403);
        }

        $field = $request->input('field');
        $allowedFields = ['status', 'is_hero', 'is_featured', 'is_verified'];

        if (! in_array($field, $allowedFields, true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid field.'], 422);
        }

        try {
            if ($field === 'status') {
                $status = (string) $request->input('value');
                $allowedStatuses = ['draft', 'pending', 'approved', 'needs_changes', 'rejected'];

                if (! in_array($status, $allowedStatuses, true)) {
                    return response()->json(['ok' => false, 'message' => 'Invalid status.'], 422);
                }

                $campaign->status = $status;

                switch ($status) {
                    case 'approved':
                        $campaign->is_draft = false;
                        $campaign->needs_changes = false;
                        $campaign->published_at = $campaign->published_at ?? now();
                        break;
                    case 'draft':
                        $campaign->is_draft = true;
                        $campaign->needs_changes = false;
                        break;
                    case 'needs_changes':
                        $campaign->is_draft = false;
                        $campaign->needs_changes = true;
                        break;
                    case 'pending':
                        $campaign->is_draft = false;
                        $campaign->needs_changes = false;
                        break;
                    case 'rejected':
                        $campaign->is_draft = false;
                        break;
                }

                $campaign->save();
            } elseif ($field === 'is_verified') {
                $raw = $request->input('value');
                $boolValue = is_bool($raw)
                    ? $raw
                    : filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($boolValue === null) {
                    return response()->json(['ok' => false, 'message' => 'Invalid boolean value.'], 422);
                }

                $this->verificationService->update($campaign, $request->user(), (bool) $boolValue);
            } else {
                $raw = $request->input('value');
                $boolValue = is_bool($raw)
                    ? $raw
                    : filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($boolValue === null) {
                    return response()->json(['ok' => false, 'message' => 'Invalid boolean value.'], 422);
                }

                if ($boolValue && $campaign->status !== 'approved') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Only approved campaigns can use this setting.',
                    ], 422);
                }

                $campaign->{$field} = (bool) $boolValue;

                if ($field === 'is_hero' && ! $boolValue) {
                    $campaign->hero_order = null;
                }

                $campaign->save();
            }

            $campaign->refresh();

            $this->archiveOrdering->clearCache();
            app(RankingScoreService::class)->refreshCampaign($campaign);

            $responseValue = $field === 'status'
                ? $campaign->status
                : (bool) $campaign->{$field};

            Log::info('Campaign inline updated', [
                'campaign_id' => $campaign->id,
                'field' => $field,
                'value' => $responseValue,
            ]);

            return response()->json([
                'ok' => true,
                'success' => true,
                'message' => 'Saved.',
                'campaign_id' => $campaign->id,
                'field' => $field,
                'value' => $responseValue,
                'campaign' => [
                    'id' => $campaign->id,
                    'status' => $campaign->status,
                    'is_hero' => (bool) $campaign->is_hero,
                    'is_verified' => (bool) $campaign->is_verified,
                    'is_featured' => (bool) $campaign->is_featured,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Campaign inline update failed', [
                'campaign_id' => $campaign->id,
                'field' => $field,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'message' => 'Could not save: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function approve(Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $campaign->update([
            'status' => 'approved',
            'is_draft' => false,
            'needs_changes' => false,
            'published_at' => $campaign->published_at ?? now(),
        ]);

        app(RankingScoreService::class)->refreshCampaign($campaign->fresh());

        if ($campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'approved'));
        }

        return back()->with('success', 'Campaign approved.');
    }

    public function reject(Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $campaign->update([
            'status' => 'rejected',
        ]);

        if ($campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'rejected', $campaign->admin_notes));
        }

        return back()->with('success', 'Campaign rejected.');
    }

    public function feature(Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $wasFeatured = $campaign->is_featured;
        $campaign->update(['is_featured' => ! $campaign->is_featured]);

        if ($campaign->is_featured && ! $wasFeatured && $campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign->fresh(), 'featured'));
        }

        app(RankingScoreService::class)->refreshCampaign($campaign->fresh());

        $message = $campaign->is_featured ? "Campaign added to Editor's Pick." : "Campaign removed from Editor's Pick.";

        return back()->with('success', $message);
    }

    public function updateHero(UpdateCampaignHeroRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $isHero = $request->boolean('is_hero');

        $campaign->update([
            'is_hero' => $isHero,
            'hero_order' => $isHero ? $request->input('hero_order') : null,
        ]);

        return back()->with('success', $isHero
            ? 'Campaign added to homepage hero slider.'
            : 'Campaign removed from homepage hero slider.');
    }

    public function updateVerification(UpdatePlatformVerificationRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $this->verificationService->update(
            $campaign,
            $request->user(),
            $request->boolean('is_verified')
        );

        return back()->with('success', $request->boolean('is_verified')
            ? 'Campaign verified by Ads of Iraq.'
            : 'Campaign verification removed.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $this->uploadService->deleteCampaignFiles($campaign);
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(?Campaign $campaign = null): array
    {
        return [
            'campaign' => $campaign,
            'industries' => Industry::orderBy('name')->get(),
            'mediumTypes' => MediumType::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'agencies' => Agency::orderBy('name')->get(),
            'productionHouses' => Agency::query()->forProductionHouseSelect()->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name', 'username', 'email']),
            'selectedTaxonomies' => $campaign
                ? $this->taxonomySyncService->selectedForForm($campaign)
                : $this->taxonomySyncService->oldInputSelections(),
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

    protected function syncArchivePlacementFromRequest(AdminCampaignStoreRequest $request, Campaign $campaign): void
    {
        $this->archivePlacement->applyToCampaign(
            $campaign->fresh(),
            enabled: $request->boolean('archive_placement_enabled'),
            page: $request->input('archive_page'),
            position: $request->input('archive_position'),
        );
    }

}
