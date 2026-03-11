<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'miner_id',
        'name',
        'slug',
        'price',
        'shares_count',
        'units_limit',
        'monthly_return_rate',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'monthly_return_rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class, 'package_id');
    }
}
