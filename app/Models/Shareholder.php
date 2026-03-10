<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shareholder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_name',
        'price',
        'billing_cycle',
        'units_limit',
        'status',
        'subscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'subscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
