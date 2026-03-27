<?php

namespace App\Models;

use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'miner_id',
        'package_id',
        'shareholder_id',
        'amount',
        'shares_owned',
        'monthly_return_rate',
        'level_bonus_rate',
        'team_bonus_rate',
        'status',
        'subscribed_at',
    ];

    protected static function booted(): void
    {
        static::created(function (UserInvestment $investment) {
            $investment->loadMissing(['user.sponsor', 'package', 'miner']);

            if (! $investment->user || ! $investment->package) {
                return;
            }

            if ((float) $investment->amount > 0) {
                $investmentTemplate = MiningPlatform::activityTemplate('investment_activated', [
                    'package_name' => $investment->package->name,
                ]);

                $investment->user->notify(new ActivityFeedNotification([
                    'category' => 'investment',
                    'status' => 'success',
                    'subject' => $investmentTemplate['subject'],
                    'message' => $investmentTemplate['message'],
                    'context_label' => 'Package',
                    'context_value' => $investment->package->name,
                    'investment_id' => $investment->id,
                    'amount' => (float) $investment->amount,
                    'amount_label' => 'Investment amount',
                ]));
            }

            if ($investment->package->slug === MiningPlatform::BASIC_UPGRADE_PACKAGE_SLUG) {
                $upgradeTemplate = MiningPlatform::activityTemplate('basic_unlocked', [
                    'package_name' => $investment->package->name,
                ]);

                $investment->user->notify(new ActivityFeedNotification([
                    'category' => 'milestone',
                    'status' => 'success',
                    'subject' => $upgradeTemplate['subject'],
                    'message' => $upgradeTemplate['message'],
                    'context_label' => 'Unlocked package',
                    'context_value' => $investment->package->name,
                    'investment_id' => $investment->id,
                    'amount' => (float) $investment->amount,
                    'amount_label' => 'Package value',
                ]));
            }

            if ((float) $investment->amount <= 0) {
                return;
            }

            $depth = 1;
            $currentSponsor = $investment->user->sponsor;

            while ($currentSponsor && $depth <= 5) {
                $templateKey = match ($depth) {
                    1 => 'team_level_1',
                    2 => 'team_level_2',
                    default => 'team_level_generic',
                };

                $template = MiningPlatform::activityTemplate($templateKey, [
                    'user_name' => $investment->user->name,
                    'user_email' => $investment->user->email,
                    'package_name' => $investment->package->name,
                    'level' => $depth,
                ]);

                $currentSponsor->notify(new ActivityFeedNotification([
                    'category' => 'reward',
                    'status' => 'success',
                    'subject' => $template['subject'],
                    'message' => $template['message'],
                    'context_label' => $depth === 1 ? 'Direct investor' : 'Network level',
                    'context_value' => $depth === 1 ? $investment->user->email : 'Level '.$depth,
                    'related_user_id' => $investment->user->id,
                    'investment_id' => $investment->id,
                    'amount' => round((float) $investment->amount * MiningPlatform::networkLevelRewardRate($depth), 2),
                    'amount_label' => $depth === 1 ? 'Reward amount' : 'Bonus amount',
                ]));

                $currentSponsor = $currentSponsor->sponsor;
                $depth++;
            }

            MiningPlatform::syncReferralRegistrationRewardsForUserAndAncestors($investment->user);
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'monthly_return_rate' => 'decimal:4',
            'level_bonus_rate' => 'decimal:4',
            'team_bonus_rate' => 'decimal:4',
            'subscribed_at' => 'datetime',
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

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'investment_id');
    }
}
