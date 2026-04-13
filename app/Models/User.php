<?php

namespace App\Models;

use App\Notifications\InvitationAwareVerifyEmail;
use App\Support\AdminTwoFactor;
use App\Support\MiningPlatform;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'kyc_status',
        'kyc_proof_path',
        'kyc_proof_original_name',
        'kyc_submitted_at',
        'kyc_reviewed_at',
        'kyc_reviewer_user_id',
        'kyc_admin_notes',
        'admin_two_factor_secret',
        'admin_two_factor_confirmed_at',
        'btc_wallet_address',
        'usdt_wallet_address',
        'bank_transfer_details',
        'password',
        'account_type',
        'role',
        'user_level_id',
        'sponsor_user_id',
        'notification_preferences',
        'last_daily_digest_sent_at',
        'last_weekly_digest_sent_at',
        'friend_invitation_emails_sent_on',
        'friend_invitation_emails_sent_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'admin_two_factor_secret',
        'active_session_token',
    ];

    protected $attributes = [
        'is_email_visible' => false,
        'kyc_status' => 'not_submitted',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function adminLabel(): string
    {
        return 'Admin #'.$this->id;
    }

    public function kycReviewer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'kyc_reviewer_user_id');
    }

    public function hasApprovedKyc(): bool
    {
        return $this->kyc_status === 'approved';
    }

    public function hasPendingKycReview(): bool
    {
        return $this->kyc_status === 'pending';
    }

    public function kycStatusLabel(): string
    {
        return match ($this->kyc_status) {
            'approved' => 'Approved',
            'pending' => 'Pending review',
            'rejected' => 'Rejected',
            default => 'Not submitted',
        };
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

    public function referralCoachingNote(): HasOne
    {
        return $this->hasOne(ReferralCoachingNote::class);
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

    public function loginEvents(): HasMany
    {
        return $this->hasMany(UserLoginEvent::class)->latest('login_at');
    }

    public function pageActivityLogs(): HasMany
    {
        return $this->hasMany(UserPageActivityLog::class)->latest('ended_at');
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

    public function payoutDestinationFor(string $method): ?string
    {
        return match ($method) {
            'btc_wallet' => $this->btc_wallet_address,
            'usdt_wallet' => $this->usdt_wallet_address,
            'bank_transfer' => $this->bank_transfer_details,
            default => null,
        };
    }

    public function hasAdminTwoFactorEnabled(): bool
    {
        return $this->isAdmin()
            && filled($this->admin_two_factor_secret)
            && $this->admin_two_factor_confirmed_at !== null;
    }

    public function hasPendingAdminTwoFactorSetup(): bool
    {
        return $this->isAdmin()
            && filled($this->admin_two_factor_secret)
            && $this->admin_two_factor_confirmed_at === null;
    }

    public function adminTwoFactorSecret(): ?string
    {
        return AdminTwoFactor::decryptSecret($this->admin_two_factor_secret);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_email_visible' => 'boolean',
            'kyc_submitted_at' => 'datetime',
            'kyc_reviewed_at' => 'datetime',
            'admin_two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'last_daily_digest_sent_at' => 'datetime',
            'last_weekly_digest_sent_at' => 'datetime',
            'friend_invitation_emails_sent_on' => 'date',
            'friend_invitation_emails_sent_count' => 'integer',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => strtolower(trim((string) $value)),
        );
    }
}
