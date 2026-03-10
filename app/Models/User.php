<?php

namespace App\Models;

use App\Notifications\InvitationAwareVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function friendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class)->latest();
    }

    public function shareholder(): HasOne
    {
        return $this->hasOne(Shareholder::class);
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
