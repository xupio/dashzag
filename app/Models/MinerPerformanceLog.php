<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinerPerformanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'miner_id',
        'logged_on',
        'revenue_usd',
        'electricity_cost_usd',
        'maintenance_cost_usd',
        'net_profit_usd',
        'hashrate_th',
        'uptime_percentage',
        'active_shares',
        'revenue_per_share_usd',
        'source',
        'auto_generated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_on' => 'date',
            'revenue_usd' => 'decimal:2',
            'electricity_cost_usd' => 'decimal:2',
            'maintenance_cost_usd' => 'decimal:2',
            'net_profit_usd' => 'decimal:2',
            'hashrate_th' => 'decimal:2',
            'uptime_percentage' => 'decimal:2',
            'revenue_per_share_usd' => 'decimal:4',
            'auto_generated_at' => 'datetime',
        ];
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }
}
