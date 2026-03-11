<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_id',
        'payout_request_id',
        'earned_on',
        'amount',
        'source',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'earned_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class, 'investment_id');
    }

    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class);
    }
}
