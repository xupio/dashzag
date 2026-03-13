<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'miner_id',
        'package_id',
        'approved_by_id',
        'amount',
        'shares_owned',
        'payment_method',
        'payment_reference',
        'payment_proof_path',
        'payment_proof_original_name',
        'notes',
        'admin_notes',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'proof_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'proof_uploaded_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}