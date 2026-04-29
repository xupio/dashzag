<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_user_id',
        'miner_id',
        'share_holding_id',
        'quantity',
        'remaining_quantity',
        'price_per_share',
        'total_price',
        'platform_fee_percent',
        'platform_fee_amount',
        'seller_net_amount',
        'status',
        'listed_at',
        'expires_at',
        'cancelled_at',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_share' => 'decimal:2',
            'total_price' => 'decimal:2',
            'platform_fee_percent' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'seller_net_amount' => 'decimal:2',
            'listed_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(ShareHolding::class, 'share_holding_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ShareSale::class, 'listing_id');
    }
}
