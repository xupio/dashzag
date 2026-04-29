<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Miner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'total_shares',
        'shares_sold',
        'share_price',
        'daily_output_usd',
        'monthly_output_usd',
        'base_monthly_return_rate',
        'status',
        'started_at',
        'opened_at',
        'sold_out_at',
        'matured_at',
        'secondary_market_opened_at',
        'closed_at',
        'near_capacity_threshold_percent',
        'maturity_days',
        'secondary_market_fee_percent',
    ];

    protected function casts(): array
    {
        return [
            'share_price' => 'decimal:2',
            'daily_output_usd' => 'decimal:2',
            'monthly_output_usd' => 'decimal:2',
            'base_monthly_return_rate' => 'decimal:4',
            'secondary_market_fee_percent' => 'decimal:2',
            'started_at' => 'datetime',
            'opened_at' => 'datetime',
            'sold_out_at' => 'datetime',
            'matured_at' => 'datetime',
            'secondary_market_opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function performanceLogs(): HasMany
    {
        return $this->hasMany(MinerPerformanceLog::class)->latest('logged_on');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(InvestmentPackage::class)->orderBy('price');
    }

    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    public function shareHoldings(): HasMany
    {
        return $this->hasMany(ShareHolding::class);
    }

    public function shareListings(): HasMany
    {
        return $this->hasMany(ShareListing::class);
    }

    public function shareSales(): HasMany
    {
        return $this->hasMany(ShareSale::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MinerStatusHistory::class)->latest();
    }
}
