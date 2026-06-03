<?php

namespace App\Services;

use App\Mail\CampaignWorkflowMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminCampaignInlineService
{
    public function __construct(
        protected PlatformVerificationService $verificationService,
        protected RankingScoreService $rankingScore,
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    public function updateStatus(Campaign $campaign, string $status, User $admin): Campaign
    {
        $previousStatus = $campaign->status;

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
            default:
                throw ValidationException::withMessages([
                    'value' => 'Invalid status.',
                ]);
        }

        $campaign->save();
        $campaign->refresh();

        $this->rankingScore->refreshCampaign($campaign);
        $this->clearCaches($campaign, 'status');

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
                'value' => 'Only approved campaigns can appear in the homepage hero slider.',
            ]);
        }

        $campaign->is_hero = $isHero;
        $campaign->hero_order = $isHero ? $campaign->hero_order : null;
        $campaign->save();
        $campaign->refresh();

        $this->clearCaches($campaign, 'is_hero');

        return $campaign;
    }

    public function updateVerified(Campaign $campaign, bool $isVerified, User $admin): Campaign
    {
        $this->verificationService->update($campaign, $admin, $isVerified);

        $campaign->refresh(['verifiedBy']);
        $this->clearCaches($campaign, 'is_verified');

        return $campaign;
    }

    public function updateEditorsPick(Campaign $campaign, bool $isEditorsPick): Campaign
    {
        if ($isEditorsPick && $campaign->status !== 'approved') {
            throw ValidationException::withMessages([
                'value' => 'Only approved campaigns can be Editor\'s Pick.',
            ]);
        }

        $wasEditorsPick = (bool) $campaign->is_featured;

        $campaign->is_featured = $isEditorsPick;
        $campaign->save();
        $campaign->refresh();

        if ($isEditorsPick && ! $wasEditorsPick && $campaign->user?->email) {
            Mail::to($campaign->user)->send(new CampaignWorkflowMail($campaign, 'featured'));
        }

        $this->rankingScore->refreshCampaign($campaign);
        $this->clearCaches($campaign, 'is_featured');

        return $campaign;
    }

    protected function clearCaches(Campaign $campaign, string $field): void
    {
        $this->archiveOrdering->clearCache();

        Log::info('Campaign inline updated', [
            'campaign_id' => $campaign->id,
            'field' => $field,
            'status' => $campaign->status,
            'is_hero' => $campaign->is_hero,
            'is_featured' => $campaign->is_featured,
            'is_verified' => $campaign->is_verified,
        ]);
    }
}
