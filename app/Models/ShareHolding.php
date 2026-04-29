<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'miner_id',
        'quantity',
        'locked_quantity',
        'avg_buy_price',
        'status',
        'last_acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'avg_buy_price' => 'decimal:2',
            'last_acquired_at' => 'datetime',
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

    public function listings(): HasMany
    {
        return $this->hasMany(ShareListing::class);
    }

    public function getTransferableQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->locked_quantity);
    }
}
