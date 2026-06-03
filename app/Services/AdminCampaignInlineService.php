<?php

namespace App\Services;

use App\Mail\CampaignWorkflowMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminCampaignInlineService
{
    public function __construct(
        protected PlatformVerificationService $verificationService,
        protected RankingScoreService $rankingScore,
    ) {}

    public function updateStatus(Campaign $campaign, string $status, User $admin): Campaign
    {
        $previousStatus = $campaign->status;

        $attributes = [
            'status' => $status,
        ];

        match ($status) {
            'approved' => $attributes += [
                'is_draft' => false,
                'needs_changes' => false,
                'published_at' => $campaign->published_at ?? now(),
            ],
            'draft' => $attributes += [
                'is_draft' => true,
                'needs_changes' => false,
            ],
            'needs_changes' => $attributes += [
                'is_draft' => false,
                'needs_changes' => true,
            ],
            'pending' => $attributes += [
                'is_draft' => false,
                'needs_changes' => false,
            ],
            'rejected' => $attributes += [
                'is_draft' => false,
            ],
            default => throw ValidationException::withMessages([
                'status' => 'Invalid status.',
            ]),
        };

        $campaign->update($attributes);
        $campaign = $campaign->fresh();

        $this->rankingScore->refreshCampaign($campaign);

        if ($status === 'approved' && $previousStatus !== 'approved' && $campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'approved'));
        }

        if ($status === 'rejected' && $previousStatus !== 'rejected' && $campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'rejected', $campaign->admin_notes));
        }

        return $campaign;
    }

    public function updateHero(Campaign $campaign, bool $isHero): Campaign
    {
        if ($isHero && $campaign->status !== 'approved') {
            throw ValidationException::withMessages([
                'is_hero' => 'Only approved campaigns can appear in the homepage hero slider.',
            ]);
        }

        $campaign->update([
            'is_hero' => $isHero,
            'hero_order' => $isHero ? $campaign->hero_order : null,
        ]);

        return $campaign->fresh();
    }

    public function updateVerified(Campaign $campaign, bool $isVerified, User $admin): Campaign
    {
        $this->verificationService->update($campaign, $admin, $isVerified);

        return $campaign->fresh(['verifiedBy']);
    }

    public function updateEditorsPick(Campaign $campaign, bool $isEditorsPick): Campaign
    {
        if ($isEditorsPick && $campaign->status !== 'approved') {
            throw ValidationException::withMessages([
                'is_featured' => 'Only approved campaigns can be Editor\'s Pick.',
            ]);
        }

        $wasEditorsPick = $campaign->is_featured;

        $campaign->update(['is_featured' => $isEditorsPick]);
        $campaign = $campaign->fresh();

        if ($isEditorsPick && ! $wasEditorsPick && $campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'featured'));
        }

        $this->rankingScore->refreshCampaign($campaign);

        return $campaign;
    }
}
