<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsor_user_id',
        'actor_user_id',
        'related_user_id',
        'user_investment_id',
        'type',
        'title',
        'message',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class, 'user_investment_id');
    }
}
