<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'miner_id',
        'seller_user_id',
        'buyer_user_id',
        'quantity',
        'price_per_share',
        'gross_amount',
        'platform_fee_percent',
        'platform_fee_amount',
        'seller_net_amount',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_share' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'platform_fee_percent' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'seller_net_amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ShareListing::class, 'listing_id');
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
