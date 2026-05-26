<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Campaign $campaign): bool
    {
        if ($campaign->status === 'approved') {
            return true;
        }

        return $user && ($user->isAdmin() || $user->id === $campaign->user_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $campaign->user_id
            && in_array($campaign->status, ['pending', 'rejected']);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin() || $user->id === $campaign->user_id;
    }

    public function moderate(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }
}
