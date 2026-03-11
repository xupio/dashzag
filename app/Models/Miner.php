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
        'share_price',
        'daily_output_usd',
        'monthly_output_usd',
        'base_monthly_return_rate',
        'status',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'share_price' => 'decimal:2',
            'daily_output_usd' => 'decimal:2',
            'monthly_output_usd' => 'decimal:2',
            'base_monthly_return_rate' => 'decimal:4',
            'started_at' => 'datetime',
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
}
