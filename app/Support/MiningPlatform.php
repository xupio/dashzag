<?php

namespace App\Support;

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\PayoutRequest;
use App\Models\ReferralEvent;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MiningPlatform
{
    public const REFERRAL_REGISTRATION_REWARD = 25.00;
    public const REFERRAL_SUBSCRIPTION_REWARD_RATE = 0.05;
    public const TEAM_DIRECT_SUBSCRIPTION_REWARD_RATE = 0.03;
    public const TEAM_INDIRECT_SUBSCRIPTION_REWARD_RATE = 0.01;

    public static function ensureDefaults(): void
    {
        $levels = [
            ['name' => 'Starter', 'slug' => 'starter', 'rank' => 1, 'bonus_rate' => 0.0000, 'minimum_referrals' => 0, 'minimum_investment' => 0, 'description' => 'Default level for every new mining user.'],
            ['name' => 'Silver', 'slug' => 'silver', 'rank' => 2, 'bonus_rate' => 0.0100, 'minimum_referrals' => 2, 'minimum_investment' => 500, 'description' => 'Unlocked after the first real growth milestones.'],
            ['name' => 'Gold', 'slug' => 'gold', 'rank' => 3, 'bonus_rate' => 0.0200, 'minimum_referrals' => 5, 'minimum_investment' => 1500, 'description' => 'Higher monthly bonus for active investors.'],
            ['name' => 'Platinum', 'slug' => 'platinum', 'rank' => 4, 'bonus_rate' => 0.0350, 'minimum_referrals' => 10, 'minimum_investment' => 3000, 'description' => 'Top tier for strong investors and referrers.'],
        ];

        foreach ($levels as $level) {
            UserLevel::updateOrCreate(['slug' => $level['slug']], $level);
        }

        if (! User::where('role', 'admin')->exists()) {
            User::query()->oldest('id')->first()?->forceFill(['role' => 'admin'])->save();
        }

        $alphaOne = self::seedMiner(
            [
                'slug' => 'alpha-one',
                'name' => 'Alpha One',
                'description' => 'Primary cloud mining unit offered to early shareholders.',
                'total_shares' => 1000,
                'share_price' => 100,
                'daily_output_usd' => 1500,
                'monthly_output_usd' => 45000,
                'base_monthly_return_rate' => 0.0800,
                'status' => 'active',
                'started_at' => now()->subMonths(3),
            ],
            [
                ['name' => 'Starter 100', 'slug' => 'starter-100', 'price' => 100, 'shares_count' => 1, 'units_limit' => 1, 'monthly_return_rate' => 0.0800, 'display_order' => 1],
                ['name' => 'Growth 500', 'slug' => 'growth-500', 'price' => 500, 'shares_count' => 5, 'units_limit' => 5, 'monthly_return_rate' => 0.0850, 'display_order' => 2],
                ['name' => 'Scale 1000', 'slug' => 'scale-1000', 'price' => 1000, 'shares_count' => 10, 'units_limit' => 10, 'monthly_return_rate' => 0.0900, 'display_order' => 3],
            ],
        );

        $betaFlux = self::seedMiner(
            [
                'slug' => 'beta-flux',
                'name' => 'Beta Flux',
                'description' => 'Expansion miner aimed at users who want a lower ticket entry and diversified output.',
                'total_shares' => 1500,
                'share_price' => 75,
                'daily_output_usd' => 1180,
                'monthly_output_usd' => 35400,
                'base_monthly_return_rate' => 0.0725,
                'status' => 'active',
                'started_at' => now()->subMonths(2),
            ],
            [
                ['name' => 'Launch 75', 'slug' => 'launch-75', 'price' => 75, 'shares_count' => 1, 'units_limit' => 1, 'monthly_return_rate' => 0.0725, 'display_order' => 1],
                ['name' => 'Momentum 300', 'slug' => 'momentum-300', 'price' => 300, 'shares_count' => 4, 'units_limit' => 4, 'monthly_return_rate' => 0.0780, 'display_order' => 2],
                ['name' => 'Velocity 750', 'slug' => 'velocity-750', 'price' => 750, 'shares_count' => 10, 'units_limit' => 10, 'monthly_return_rate' => 0.0825, 'display_order' => 3],
            ],
        );

        self::seedPerformanceLogs($alphaOne, 1325, 37, 485, 4, 97.40, 0.22);
        self::seedPerformanceLogs($betaFlux, 980, 29, 430, 3, 98.10, 0.14);
    }

    public static function activeMiners(): Collection
    {
        self::ensureDefaults();

        return Miner::query()
            ->whereIn('status', ['active', 'maintenance'])
            ->orderByRaw("CASE WHEN slug = 'alpha-one' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    public static function resolveMiner(?string $slug = null): Miner
    {
        self::ensureDefaults();

        return Miner::query()
            ->when($slug, fn ($query) => $query->where('slug', $slug))
            ->orderByRaw("CASE WHEN slug = 'alpha-one' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->firstOrFail();
    }

    public static function createMiner(array $attributes): Miner
    {
        self::ensureDefaults();

        $slug = Str::slug($attributes['slug'] ?: $attributes['name']);

        $miner = Miner::create([
            'name' => $attributes['name'],
            'slug' => $slug,
            'description' => $attributes['description'] ?? null,
            'total_shares' => $attributes['total_shares'],
            'share_price' => $attributes['share_price'],
            'daily_output_usd' => $attributes['daily_output_usd'],
            'monthly_output_usd' => $attributes['monthly_output_usd'],
            'base_monthly_return_rate' => $attributes['base_monthly_return_rate'],
            'status' => $attributes['status'],
            'started_at' => now(),
        ]);

        self::createDefaultPackagesForMiner($miner);
        self::seedPerformanceLogs(
            $miner,
            max((float) $miner->daily_output_usd * 0.88, 1),
            max((float) $miner->daily_output_usd * 0.02, 1),
            420,
            3,
            97.80,
            0.12,
        );

        return $miner;
    }

    public static function syncUserLevel(User $user): UserLevel
    {
        self::ensureDefaults();

        $registeredReferrals = $user->friendInvitations()->whereNotNull('registered_at')->count();
        $totalInvestment = (float) $user->investments()->sum('amount');

        $level = UserLevel::query()->orderByDesc('rank')->get()->first(function (UserLevel $candidate) use ($registeredReferrals, $totalInvestment) {
            return $registeredReferrals >= $candidate->minimum_referrals && $totalInvestment >= $candidate->minimum_investment;
        }) ?? UserLevel::query()->orderBy('rank')->firstOrFail();

        if ($user->user_level_id !== $level->id) {
            $user->forceFill(['user_level_id' => $level->id])->save();
        }

        return $level;
    }

    public static function teamBonusRate(User $user): float
    {
        $activeDirectInvestors = $user->sponsoredUsers()
            ->whereHas('investments', fn ($query) => $query->where('status', 'active'))
            ->count();

        return match (true) {
            $activeDirectInvestors >= 5 => 0.0100,
            $activeDirectInvestors >= 3 => 0.0050,
            $activeDirectInvestors >= 1 => 0.0025,
            default => 0.0000,
        };
    }

    public static function refreshInvestmentBonusRates(User $user): User
    {
        $level = self::syncUserLevel($user);
        $teamBonusRate = self::teamBonusRate($user);

        $user->investments()
            ->where('status', 'active')
            ->update([
                'level_bonus_rate' => $level->bonus_rate,
                'team_bonus_rate' => $teamBonusRate,
            ]);

        return $user->fresh(['userLevel', 'investments.package', 'investments.miner']);
    }

    public static function assignSponsorFromInvitations(User $user): ?User
    {
        if ($user->sponsor_user_id) {
            return $user->sponsor;
        }

        $invitation = FriendInvitation::with('user')
            ->where('email', $user->email)
            ->oldest('id')
            ->first();

        if (! $invitation?->user) {
            return null;
        }

        $user->forceFill(['sponsor_user_id' => $invitation->user_id])->save();

        self::recordReferralEvent(
            $invitation->user,
            'team_registered',
            'A referred user completed registration',
            $user->name.' verified their account and is now attached to your team.',
            $user,
            null,
        );

        return $invitation->user;
    }

    public static function totalSharesSold(Miner $miner): int
    {
        return (int) $miner->investments()->where('status', 'active')->sum('shares_owned');
    }

    public static function expectedMonthlyEarnings(User $user): float
    {
        return (float) $user->investments()->where('status', 'active')->get()->sum(function (UserInvestment $investment) {
            return (float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate);
        });
    }

    public static function generateMonthlyEarnings(User $user, ?Carbon $month = null): Collection
    {
        $period = ($month ?? now())->copy()->startOfMonth();

        return $user->investments()->with('package')->where('status', 'active')->get()->map(function (UserInvestment $investment) use ($user, $period) {
            $amount = round((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate), 2);

            return Earning::firstOrCreate(
                ['user_id' => $user->id, 'investment_id' => $investment->id, 'earned_on' => $period->toDateString(), 'source' => 'mining_return'],
                ['amount' => $amount, 'status' => 'available', 'notes' => 'Monthly mining return generated for '.$period->format('F Y').'.'],
            );
        });
    }

    public static function awardReferralRegistration(User $registeredUser): Collection
    {
        return FriendInvitation::query()->with('user')->where('email', $registeredUser->email)->get()->map(function (FriendInvitation $invitation) use ($registeredUser) {
            return Earning::firstOrCreate(
                ['user_id' => $invitation->user_id, 'investment_id' => null, 'earned_on' => now()->toDateString(), 'source' => 'referral_registration', 'notes' => 'Referral registration reward for '.$registeredUser->email.'.'],
                ['amount' => self::REFERRAL_REGISTRATION_REWARD, 'status' => 'available'],
            );
        });
    }

    public static function awardReferralSubscription(User $referredUser, UserInvestment $investment): Collection
    {
        $rewardAmount = round((float) $investment->amount * self::REFERRAL_SUBSCRIPTION_REWARD_RATE, 2);

        $rewards = FriendInvitation::query()->with('user')->where('email', $referredUser->email)->get()->map(function (FriendInvitation $invitation) use ($referredUser, $investment, $rewardAmount) {
            return Earning::firstOrCreate(
                ['user_id' => $invitation->user_id, 'investment_id' => null, 'earned_on' => now()->toDateString(), 'source' => 'referral_subscription', 'notes' => 'Referral subscription reward for '.$referredUser->email.' on investment #'.$investment->id.'.'],
                ['amount' => $rewardAmount, 'status' => 'available'],
            );
        });

        self::awardTeamSubscriptionRewards($referredUser, $investment);

        return $rewards;
    }

    public static function awardTeamSubscriptionRewards(User $referredUser, UserInvestment $investment): void
    {
        $sponsor = $referredUser->sponsor()->first();

        if ($sponsor) {
            $directRewardAmount = round((float) $investment->amount * self::TEAM_DIRECT_SUBSCRIPTION_REWARD_RATE, 2);

            Earning::firstOrCreate(
                [
                    'user_id' => $sponsor->id,
                    'investment_id' => null,
                    'earned_on' => now()->toDateString(),
                    'source' => 'team_subscription_bonus',
                    'notes' => 'Team subscription bonus for '.$referredUser->email.' on investment #'.$investment->id.'.',
                ],
                [
                    'amount' => $directRewardAmount,
                    'status' => 'available',
                ],
            );

            self::recordReferralEvent(
                $sponsor,
                'team_subscription',
                'A team investor subscribed',
                $referredUser->name.' subscribed to '.$investment->package?->name.' under your team.',
                $referredUser,
                $investment,
            );

            self::refreshInvestmentBonusRates($sponsor->fresh());

            $secondLevelSponsor = $sponsor->sponsor()->first();
            if ($secondLevelSponsor) {
                $indirectRewardAmount = round((float) $investment->amount * self::TEAM_INDIRECT_SUBSCRIPTION_REWARD_RATE, 2);

                Earning::firstOrCreate(
                    [
                        'user_id' => $secondLevelSponsor->id,
                        'investment_id' => null,
                        'earned_on' => now()->toDateString(),
                        'source' => 'team_downline_bonus',
                        'notes' => 'Second-level team bonus for '.$referredUser->email.' on investment #'.$investment->id.'.',
                    ],
                    [
                        'amount' => $indirectRewardAmount,
                        'status' => 'available',
                    ],
                );

                self::recordReferralEvent(
                    $secondLevelSponsor,
                    'team_downline_subscription',
                    'A second-level investor subscribed',
                    $referredUser->name.' subscribed in your extended network.',
                    $referredUser,
                    $investment,
                );
            }
        }
    }

    public static function recordReferralEvent(User $sponsor, string $type, string $title, ?string $message = null, ?User $relatedUser = null, ?UserInvestment $investment = null): ReferralEvent
    {
        return ReferralEvent::create([
            'sponsor_user_id' => $sponsor->id,
            'actor_user_id' => $relatedUser?->id,
            'related_user_id' => $relatedUser?->id,
            'user_investment_id' => $investment?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public static function createPayoutRequest(User $user, float $amount, string $method, string $destination, ?string $notes = null): PayoutRequest
    {
        return DB::transaction(function () use ($user, $amount, $method, $destination, $notes) {
            $availableEarnings = $user->earnings()->where('status', 'available')->orderBy('earned_on')->orderBy('id')->get();
            $availableBalance = (float) $availableEarnings->sum('amount');

            if ($amount > $availableBalance) {
                throw new RuntimeException('Requested payout exceeds available balance.');
            }

            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $method,
                'destination' => $destination,
                'notes' => $notes,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            $remaining = round($amount, 2);

            foreach ($availableEarnings as $earning) {
                if ($remaining <= 0) {
                    break;
                }

                $earningAmount = round((float) $earning->amount, 2);

                if ($earningAmount <= $remaining) {
                    $earning->forceFill(['status' => 'payout_pending', 'payout_request_id' => $payoutRequest->id])->save();
                    $remaining = round($remaining - $earningAmount, 2);
                    continue;
                }

                $earning->forceFill(['amount' => round($earningAmount - $remaining, 2)])->save();

                Earning::create([
                    'user_id' => $earning->user_id,
                    'investment_id' => $earning->investment_id,
                    'payout_request_id' => $payoutRequest->id,
                    'earned_on' => $earning->earned_on,
                    'amount' => $remaining,
                    'source' => $earning->source,
                    'status' => 'payout_pending',
                    'notes' => $earning->notes,
                ]);

                $remaining = 0;
            }

            return $payoutRequest;
        });
    }

    public static function approvePayoutRequest(PayoutRequest $payoutRequest, ?string $adminNotes = null): PayoutRequest
    {
        if ($payoutRequest->status !== 'pending') {
            return $payoutRequest;
        }

        $payoutRequest->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
            'admin_notes' => $adminNotes ?: $payoutRequest->admin_notes,
        ])->save();

        return $payoutRequest->fresh();
    }

    public static function markPayoutRequestPaid(PayoutRequest $payoutRequest, ?string $transactionReference = null, ?string $adminNotes = null): PayoutRequest
    {
        if ($payoutRequest->status === 'paid') {
            return $payoutRequest;
        }

        DB::transaction(function () use ($payoutRequest, $transactionReference, $adminNotes) {
            $payoutRequest->forceFill([
                'status' => 'paid',
                'transaction_reference' => $transactionReference ?: $payoutRequest->transaction_reference,
                'admin_notes' => $adminNotes ?: $payoutRequest->admin_notes,
                'approved_at' => $payoutRequest->approved_at ?: now(),
                'processed_at' => now(),
            ])->save();

            $payoutRequest->earnings()->update(['status' => 'paid']);
        });

        return $payoutRequest->fresh();
    }

    public static function walletSummary(User $user): array
    {
        $earnings = $user->earnings()->get();

        return [
            'available' => (float) $earnings->where('status', 'available')->sum('amount'),
            'pending' => (float) $earnings->whereIn('status', ['pending', 'payout_pending'])->sum('amount'),
            'paid' => (float) $earnings->where('status', 'paid')->sum('amount'),
            'total' => (float) $earnings->sum('amount'),
        ];
    }

    protected static function createDefaultPackagesForMiner(Miner $miner): void
    {
        $definitions = [
            ['name' => 'Launch', 'suffix' => 'launch', 'shares_count' => 1, 'units_limit' => 1, 'price_multiplier' => 1, 'rate_bonus' => 0.0000, 'display_order' => 1],
            ['name' => 'Growth', 'suffix' => 'growth', 'shares_count' => 5, 'units_limit' => 5, 'price_multiplier' => 5, 'rate_bonus' => 0.0050, 'display_order' => 2],
            ['name' => 'Scale', 'suffix' => 'scale', 'shares_count' => 10, 'units_limit' => 10, 'price_multiplier' => 10, 'rate_bonus' => 0.0100, 'display_order' => 3],
        ];

        foreach ($definitions as $definition) {
            $price = round((float) $miner->share_price * $definition['price_multiplier'], 2);

            InvestmentPackage::updateOrCreate(
                ['slug' => $miner->slug.'-'.$definition['suffix']],
                [
                    'miner_id' => $miner->id,
                    'name' => $definition['name'].' '.number_format($price, 0, '.', ''),
                    'price' => $price,
                    'shares_count' => $definition['shares_count'],
                    'units_limit' => $definition['units_limit'],
                    'monthly_return_rate' => round((float) $miner->base_monthly_return_rate + $definition['rate_bonus'], 4),
                    'display_order' => $definition['display_order'],
                    'is_active' => true,
                ],
            );
        }
    }

    protected static function seedMiner(array $minerData, array $packages): Miner
    {
        $miner = Miner::updateOrCreate(
            ['slug' => $minerData['slug']],
            $minerData,
        );

        foreach ($packages as $package) {
            InvestmentPackage::updateOrCreate(
                ['slug' => $package['slug']],
                array_merge($package, ['miner_id' => $miner->id, 'is_active' => true]),
            );
        }

        return $miner;
    }

    protected static function seedPerformanceLogs(
        Miner $miner,
        float $revenueBase,
        float $revenueStep,
        float $hashrateBase,
        float $hashrateStep,
        float $uptimeBase,
        float $uptimeStep,
    ): void {
        foreach (range(6, 0) as $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo)->toDateString();
            $offset = 6 - $daysAgo;

            DB::table('miner_performance_logs')->updateOrInsert(
                ['miner_id' => $miner->id, 'logged_on' => $date],
                [
                    'revenue_usd' => $revenueBase + ($offset * $revenueStep),
                    'hashrate_th' => $hashrateBase + ($offset * $hashrateStep),
                    'uptime_percentage' => $uptimeBase + ($offset * $uptimeStep),
                    'notes' => 'Auto-generated baseline log for dashboard visibility.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
