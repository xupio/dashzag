<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'miner_id',
        'package_id',
        'shareholder_id',
        'amount',
        'shares_owned',
        'monthly_return_rate',
        'level_bonus_rate',
        'team_bonus_rate',
        'status',
        'subscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'monthly_return_rate' => 'decimal:4',
            'level_bonus_rate' => 'decimal:4',
            'team_bonus_rate' => 'decimal:4',
            'subscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InvestmentPackage::class, 'package_id');
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'investment_id');
    }
}
