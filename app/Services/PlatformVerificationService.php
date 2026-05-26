<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformVerificationService
{
    /**
     * @param  Model&object{is_verified: bool, verified_at: ?\Illuminate\Support\Carbon, verified_by: ?int}  $model
     */
    public function update(Model $model, User $admin, bool $verified): void
    {
        if ($verified) {
            $model->forceFill([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ])->save();

            return;
        }

        $model->forceFill([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ])->save();
    }
}
