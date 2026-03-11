<?php

namespace App\Support;

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MiningPlatform
{
    public const REFERRAL_REGISTRATION_REWARD = 25.00;
    public const REFERRAL_SUBSCRIPTION_REWARD_RATE = 0.05;

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

        $miner = Miner::updateOrCreate(
            ['slug' => 'alpha-one'],
            ['name' => 'Alpha One', 'description' => 'Primary cloud mining unit offered to early shareholders.', 'total_shares' => 1000, 'share_price' => 100, 'daily_output_usd' => 1500, 'monthly_output_usd' => 45000, 'base_monthly_return_rate' => 0.0800, 'status' => 'active', 'started_at' => now()->subMonths(3)],
        );

        $packages = [
            ['name' => 'Starter 100', 'slug' => 'starter-100', 'price' => 100, 'shares_count' => 1, 'units_limit' => 1, 'monthly_return_rate' => 0.0800, 'display_order' => 1],
            ['name' => 'Growth 500', 'slug' => 'growth-500', 'price' => 500, 'shares_count' => 5, 'units_limit' => 5, 'monthly_return_rate' => 0.0850, 'display_order' => 2],
            ['name' => 'Scale 1000', 'slug' => 'scale-1000', 'price' => 1000, 'shares_count' => 10, 'units_limit' => 10, 'monthly_return_rate' => 0.0900, 'display_order' => 3],
        ];

        foreach ($packages as $package) {
            InvestmentPackage::updateOrCreate(['slug' => $package['slug']], array_merge($package, ['miner_id' => $miner->id, 'is_active' => true]));
        }

        foreach (range(6, 0) as $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo)->toDateString();
            $offset = 6 - $daysAgo;

            DB::table('miner_performance_logs')->updateOrInsert(
                ['miner_id' => $miner->id, 'logged_on' => $date],
                ['revenue_usd' => 1325 + ($offset * 37), 'hashrate_th' => 485 + ($offset * 4), 'uptime_percentage' => 97.40 + ($offset * 0.22), 'notes' => 'Auto-generated baseline log for dashboard visibility.', 'updated_at' => now(), 'created_at' => now()],
            );
        }
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

    public static function totalSharesSold(Miner $miner): int
    {
        return (int) $miner->investments()->where('status', 'active')->sum('shares_owned');
    }

    public static function expectedMonthlyEarnings(User $user): float
    {
        return (float) $user->investments()->where('status', 'active')->get()->sum(function (UserInvestment $investment) {
            return (float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate);
        });
    }

    public static function generateMonthlyEarnings(User $user, ?Carbon $month = null): Collection
    {
        $period = ($month ?? now())->copy()->startOfMonth();

        return $user->investments()->with('package')->where('status', 'active')->get()->map(function (UserInvestment $investment) use ($user, $period) {
            $amount = round((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate), 2);

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

        return FriendInvitation::query()->with('user')->where('email', $referredUser->email)->get()->map(function (FriendInvitation $invitation) use ($referredUser, $investment, $rewardAmount) {
            return Earning::firstOrCreate(
                ['user_id' => $invitation->user_id, 'investment_id' => null, 'earned_on' => now()->toDateString(), 'source' => 'referral_subscription', 'notes' => 'Referral subscription reward for '.$referredUser->email.' on investment #'.$investment->id.'.'],
                ['amount' => $rewardAmount, 'status' => 'available'],
            );
        });
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

    public static function approvePayoutRequest(PayoutRequest $payoutRequest): PayoutRequest
    {
        if ($payoutRequest->status !== 'pending') {
            return $payoutRequest;
        }

        $payoutRequest->forceFill(['status' => 'approved'])->save();

        return $payoutRequest->fresh();
    }

    public static function markPayoutRequestPaid(PayoutRequest $payoutRequest): PayoutRequest
    {
        if ($payoutRequest->status === 'paid') {
            return $payoutRequest;
        }

        DB::transaction(function () use ($payoutRequest) {
            $payoutRequest->forceFill([
                'status' => 'paid',
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
}
