<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'rank',
        'bonus_rate',
        'minimum_referrals',
        'minimum_investment',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'bonus_rate' => 'decimal:4',
            'minimum_referrals' => 'integer',
            'minimum_investment' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
