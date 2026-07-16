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
        'gateway_provider',
        'payment_reference',
        'gateway_reference',
        'gateway_status',
        'gateway_redirect_url',
        'gateway_embedded_url',
        'gateway_payload',
        'payment_proof_path',
        'payment_proof_original_name',
        'notes',
        'admin_notes',
        'status',
        'submitted_at',
        'approved_at',
        'gateway_paid_at',
        'rejected_at',
        'cancelled_at',
        'proof_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'gateway_paid_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
