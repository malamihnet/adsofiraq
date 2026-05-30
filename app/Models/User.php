<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformVerification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasPlatformVerification, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'website',
        'instagram_url',
        'tiktok_url',
        'facebook_url',
        'linkedin_url',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'username_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function approvedCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class)->approved();
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'bookmarks')->withTimestamps();
    }

    public function campaignWatchers(): HasMany
    {
        return $this->hasMany(CampaignWatcher::class);
    }

    public function watchingCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_watchers')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function canChangeUsername(): bool
    {
        if ($this->username_changed_at === null) {
            return true;
        }

        return $this->username_changed_at->copy()->addDays(60)->isPast();
    }

    public function nextUsernameChangeAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->canChangeUsername()) {
            return null;
        }

        return $this->username_changed_at?->copy()->addDays(60);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/'.$this->avatar);
        }

        return placeholderUrl('square');
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * Send the branded email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
