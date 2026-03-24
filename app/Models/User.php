<?php

namespace App\Models;

use App\Notifications\InvitationAwareVerifyEmail;
use App\Support\MiningPlatform;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'is_email_visible',
        'profile_photo_path',
        'password',
        'account_type',
        'role',
        'user_level_id',
        'sponsor_user_id',
        'notification_preferences',
        'last_daily_digest_sent_at',
        'last_weekly_digest_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'is_email_visible' => false,
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public static function defaultNotificationPreferences(): array
    {
        return array_replace_recursive(MiningPlatform::notificationDefaultPreferences(), [
            'digest' => [
                'in_app' => true,
                'email' => false,
                'frequency' => 'weekly',
            ],
        ]);
    }

    public function notificationPreferences(): array
    {
        return array_replace_recursive(static::defaultNotificationPreferences(), $this->notification_preferences ?? []);
    }

    public function notificationChannelsFor(string $category): array
    {
        $normalizedCategory = $this->normalizeNotificationCategory($category);
        $preferences = $this->notificationPreferences()[$normalizedCategory] ?? ['in_app' => true, 'email' => false];
        $channels = [];

        if ($preferences['in_app'] ?? false) {
            $channels[] = 'database';
        }

        if ($preferences['email'] ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function digestFrequency(): string
    {
        $frequency = $this->notificationPreferences()['digest']['frequency'] ?? 'weekly';

        return in_array($frequency, ['daily', 'weekly'], true) ? $frequency : 'weekly';
    }

    public function normalizeNotificationCategory(string $category): string
    {
        return match ($category) {
            'referral' => 'network',
            default => $category,
        };
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    public function sponsoredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'sponsor_user_id')->latest('created_at');
    }

    public function friendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class)->latest();
    }

    public function shareholder(): HasOne
    {
        return $this->hasOne(Shareholder::class);
    }

    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class)->latest('subscribed_at');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(InternalMessage::class, 'sender_id')->latest();
    }

    public function receivedMessageRecords(): HasMany
    {
        return $this->hasMany(InternalMessageRecipient::class)->latest();
    }
    public function investmentOrders(): HasMany
    {
        return $this->hasMany(InvestmentOrder::class)->latest('submitted_at');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class)->latest('earned_on');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class)->latest('requested_at');
    }

    public function referralEvents(): HasMany
    {
        return $this->hasMany(ReferralEvent::class, 'sponsor_user_id')->latest();
    }

    public function mockManagerScenarios(): HasMany
    {
        return $this->hasMany(MockManagerScenario::class)->latest();
    }

    public function sendEmailVerificationNotification(): void
    {
        $friendInvitations = FriendInvitation::with('user:id,name')
            ->where('email', $this->email)
            ->get();

        $this->notify(new InvitationAwareVerifyEmail($friendInvitations));
    }

    public function displayEmail(): string
    {
        if (! $this->is_email_visible) {
            return 'Email hidden';
        }

        return strtolower((string) $this->email);
    }

    public function profilePhotoUrl(): string
    {
        if ($this->profile_photo_path) {
            return route('profile.photo', $this);
        }

        return asset('branding/zag-smal.png');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_email_visible' => 'boolean',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'last_daily_digest_sent_at' => 'datetime',
            'last_weekly_digest_sent_at' => 'datetime',
        ];
    }
}



