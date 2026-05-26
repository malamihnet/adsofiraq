<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignRevision;
use App\Services\CampaignRevisionApplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignRevisionController extends Controller
{
    public function __construct(
        protected CampaignRevisionApplier $applier,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');

        $revisions = CampaignRevision::query()
            ->with(['campaign', 'user', 'approvedBy'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.revisions.index', [
            'status' => $status,
            'revisions' => $revisions,
        ]);
    }

    public function show(CampaignRevision $revision): View
    {
        $revision->loadMissing(['campaign.brands', 'campaign.agencies', 'user', 'approvedBy']);

        $campaign = $revision->campaign;
        $payload = (array) ($revision->revision_payload ?? []);

        $current = [
            'title' => $campaign->title,
            'published_at' => optional($campaign->published_at)?->format('Y-m-d'),
            'description' => $campaign->description,
            'credits' => $campaign->credits,
            'is_student' => (bool) $campaign->is_student,
            'is_nsfw' => (bool) $campaign->is_nsfw,
            'submission_notes' => $campaign->submission_notes,
        ];

        $proposed = [
            'title' => $payload['title'] ?? null,
            'published_at' => $payload['published_at'] ?? null,
            'description' => $payload['description'] ?? null,
            'credits' => $payload['credits'] ?? null,
            'is_student' => $payload['is_student'] ?? null,
            'is_nsfw' => $payload['is_nsfw'] ?? null,
            'submission_notes' => $payload['submission_notes'] ?? null,
        ];

        $changed = collect($proposed)
            ->filter(fn ($value, $key) => array_key_exists($key, $current) && $value !== null && $value != $current[$key])
            ->keys()
            ->all();

        return view('admin.revisions.show', [
            'revision' => $revision,
            'campaign' => $campaign,
            'current' => $current,
            'proposed' => $proposed,
            'changedKeys' => $changed,
            'taxonomies' => (array) ($payload['taxonomies'] ?? []),
        ]);
    }

    public function approve(CampaignRevision $revision): RedirectResponse
    {
        if ($revision->status !== 'pending') {
            return back()->with('error', 'Only pending revisions can be approved.');
        }

        $this->applier->apply($revision);
        $revision->approve(auth()->user());

        return redirect()
            ->route('admin.revisions.show', $revision)
            ->with('success', 'Campaign update approved and published.');
    }

    public function reject(Request $request, CampaignRevision $revision): RedirectResponse
    {
        if ($revision->status !== 'pending') {
            return back()->with('error', 'Only pending revisions can be rejected.');
        }

        $notes = $request->input('review_notes');
        $revision->reject(auth()->user(), is_string($notes) ? $notes : null);

        return redirect()
            ->route('admin.revisions.show', $revision)
            ->with('success', 'Campaign update rejected.');
    }
}

