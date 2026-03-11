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
        'hashrate_th',
        'uptime_percentage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_on' => 'date',
            'revenue_usd' => 'decimal:2',
            'hashrate_th' => 'decimal:2',
            'uptime_percentage' => 'decimal:2',
        ];
    }

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }
}
