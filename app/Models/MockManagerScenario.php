<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockManagerScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'miner_id',
        'package_id',
        'name',
        'inputs',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
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
}
