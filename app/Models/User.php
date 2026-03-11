<?php

namespace App\Models;

use App\Notifications\InvitationAwareVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'role',
        'user_level_id',
        'sponsor_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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

    public function sendEmailVerificationNotification(): void
    {
        $friendInvitations = FriendInvitation::with('user:id,name')
            ->where('email', $this->email)
            ->get();

        $this->notify(new InvitationAwareVerifyEmail($friendInvitations));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
