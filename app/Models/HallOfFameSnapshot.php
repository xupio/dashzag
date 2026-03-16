<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallOfFameSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'period_start',
        'period_end',
        'user_id',
        'rank_position',
        'score',
        'profile_power_score',
        'rank_label',
        'highlights',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'rank_position' => 'integer',
        'score' => 'integer',
        'profile_power_score' => 'integer',
        'highlights' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
