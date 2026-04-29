<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinerStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'miner_id',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
    ];

    public function miner(): BelongsTo
    {
        return $this->belongsTo(Miner::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
