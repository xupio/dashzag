<?php

namespace App\Support;

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\PayoutRequest;
use App\Models\PlatformSetting;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
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
    public const FREE_STARTER_PACKAGE_SLUG = 'starter-free';
    public const BASIC_UPGRADE_PACKAGE_SLUG = 'starter-100';
    public const FREE_STARTER_VERIFIED_INVITES_REQUIRED = 20;
    public const FREE_STARTER_DIRECT_BASIC_REQUIRED = 1;
    public const REFERRAL_REGISTRATION_REWARD = 25.00;
    public const REFERRAL_SUBSCRIPTION_REWARD_RATE = 0.05;
    public const TEAM_DIRECT_SUBSCRIPTION_REWARD_RATE = 0.03;
    public const TEAM_INDIRECT_SUBSCRIPTION_REWARD_RATE = 0.01;

    public static function defaultRewardSettings(): array
    {
        return [
            'free_starter_verified_invites_required' => (string) self::FREE_STARTER_VERIFIED_INVITES_REQUIRED,
            'free_starter_direct_basic_required' => (string) self::FREE_STARTER_DIRECT_BASIC_REQUIRED,
            'referral_registration_reward' => (string) self::REFERRAL_REGISTRATION_REWARD,
            'referral_subscription_reward_rate' => (string) self::REFERRAL_SUBSCRIPTION_REWARD_RATE,
            'team_direct_subscription_reward_rate' => (string) self::TEAM_DIRECT_SUBSCRIPTION_REWARD_RATE,
            'team_indirect_subscription_reward_rate' => (string) self::TEAM_INDIRECT_SUBSCRIPTION_REWARD_RATE,
            'invitation_bonus_after_10_rate' => '0.0030',
            'invitation_bonus_after_20_rate' => '0.0075',
            'invitation_bonus_after_50_rate' => '0.0150',
            'team_bonus_after_1_investor_rate' => '0.0025',
            'team_bonus_after_3_investor_rate' => '0.0050',
            'team_bonus_after_5_investor_rate' => '0.0100',
            'team_level_3_subscription_reward_rate' => '0.0050',
            'team_level_4_subscription_reward_rate' => '0.0025',
            'team_level_5_subscription_reward_rate' => '0.0010',
        ];
    }

    public static function rewardSettings(): array
    {
        self::ensureDefaults();

        return PlatformSetting::query()
            ->whereIn('key', array_keys(self::defaultRewardSettings()))
            ->pluck('value', 'key')
            ->all();
    }

    public static function rewardSetting(string $key): string
    {
        return self::rewardSettings()[$key] ?? self::defaultRewardSettings()[$key] ?? '';
    }

    public static function updateRewardSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }
    }
    public static function defaultPlatformSettings(): array
    {
        return [
            'new_miner_total_shares' => '1000',
            'new_miner_share_price' => '100',
            'new_miner_daily_output_usd' => '1200',
            'new_miner_monthly_output_usd' => '36000',
            'new_miner_base_monthly_return_rate' => '0.0800',
            'launch_package_name' => 'Launch',
            'launch_package_shares_count' => '1',
            'launch_package_units_limit' => '1',
            'launch_package_price_multiplier' => '1',
            'launch_package_rate_bonus' => '0.0000',
            'growth_package_name' => 'Growth',
            'growth_package_shares_count' => '5',
            'growth_package_units_limit' => '5',
            'growth_package_price_multiplier' => '5',
            'growth_package_rate_bonus' => '0.0050',
            'scale_package_name' => 'Scale',
            'scale_package_shares_count' => '10',
            'scale_package_units_limit' => '10',
            'scale_package_price_multiplier' => '10',
            'scale_package_rate_bonus' => '0.0100',
        ];
    }

    public static function platformSettings(): array
    {
        self::ensureDefaults();

        return PlatformSetting::query()
            ->whereIn('key', array_keys(self::defaultPlatformSettings()))
            ->pluck('value', 'key')
            ->all();
    }

    public static function platformSetting(string $key): string
    {
        return self::platformSettings()[$key] ?? self::defaultPlatformSettings()[$key] ?? '';
    }

    public static function updatePlatformSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }
    }

    public static function networkLevelRewardRate(int $depth): float
    {
        return match ($depth) {
            1 => (float) self::rewardSetting('team_direct_subscription_reward_rate'),
            2 => (float) self::rewardSetting('team_indirect_subscription_reward_rate'),
            3 => (float) self::rewardSetting('team_level_3_subscription_reward_rate'),
            4 => (float) self::rewardSetting('team_level_4_subscription_reward_rate'),
            5 => (float) self::rewardSetting('team_level_5_subscription_reward_rate'),
            default => 0.0000,
        };
    }

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

        foreach (array_merge(self::defaultRewardSettings(), self::defaultPlatformSettings()) as $key => $value) {
            PlatformSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
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
                ['name' => 'Starter Free', 'slug' => self::FREE_STARTER_PACKAGE_SLUG, 'price' => 0, 'shares_count' => 0, 'units_limit' => 1, 'monthly_return_rate' => 0.0000, 'display_order' => 0],
                ['name' => 'Basic 100', 'slug' => self::BASIC_UPGRADE_PACKAGE_SLUG, 'price' => 100, 'shares_count' => 1, 'units_limit' => 1, 'monthly_return_rate' => 0.0800, 'display_order' => 1],
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

    public static function freeStarterPackage(): InvestmentPackage
    {
        self::ensureDefaults();

        return InvestmentPackage::with('miner')->where('slug', self::FREE_STARTER_PACKAGE_SLUG)->firstOrFail();
    }

    public static function basicUpgradePackage(): InvestmentPackage
    {
        self::ensureDefaults();

        return InvestmentPackage::with('miner')->where('slug', self::BASIC_UPGRADE_PACKAGE_SLUG)->firstOrFail();
    }

    public static function ensureStarterPackage(User $user): UserInvestment
    {
        self::ensureDefaults();

        $existingStarter = $user->investments()
            ->whereHas('package', fn ($query) => $query->where('slug', self::FREE_STARTER_PACKAGE_SLUG))
            ->first();

        if ($existingStarter) {
            return $existingStarter;
        }

        $package = self::freeStarterPackage();
        $level = self::syncUserLevel($user);

        $shareholder = Shareholder::updateOrCreate(
            ['user_id' => $user->id],
            [
                'package_name' => $package->name,
                'price' => $package->price,
                'billing_cycle' => 'monthly',
                'units_limit' => $package->units_limit,
                'status' => 'starter',
                'subscribed_at' => now(),
            ],
        );

        $investment = UserInvestment::create([
            'user_id' => $user->id,
            'miner_id' => $package->miner_id,
            'package_id' => $package->id,
            'shareholder_id' => $shareholder->id,
            'amount' => $package->price,
            'shares_owned' => $package->shares_count,
            'monthly_return_rate' => $package->monthly_return_rate,
            'level_bonus_rate' => $level->bonus_rate,
            'team_bonus_rate' => 0,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        if (! $user->account_type || $user->account_type === 'user') {
            $user->forceFill(['account_type' => 'starter'])->save();
        }

        return $investment;
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
        $totalInvestment = (float) $user->investments()->where('amount', '>', 0)->sum('amount');

        $level = UserLevel::query()->orderByDesc('rank')->get()->first(function (UserLevel $candidate) use ($registeredReferrals, $totalInvestment) {
            return $registeredReferrals >= $candidate->minimum_referrals && $totalInvestment >= $candidate->minimum_investment;
        }) ?? UserLevel::query()->orderBy('rank')->firstOrFail();

        if ($user->user_level_id !== $level->id) {
            $user->forceFill(['user_level_id' => $level->id])->save();
        }

        return $level;
    }

    public static function invitationBonusRate(User $user): float
    {
        $verifiedInvites = $user->friendInvitations()->whereNotNull('verified_at')->count();

        return match (true) {
            $verifiedInvites >= 50 => (float) self::rewardSetting('invitation_bonus_after_50_rate'),
            $verifiedInvites >= 20 => (float) self::rewardSetting('invitation_bonus_after_20_rate'),
            $verifiedInvites >= 10 => (float) self::rewardSetting('invitation_bonus_after_10_rate'),
            default => 0.0000,
        };
    }

    public static function teamBonusRate(User $user): float
    {
        $activeDirectInvestors = $user->sponsoredUsers()
            ->whereHas('investments', fn ($query) => $query->where('status', 'active')->where('amount', '>', 0))
            ->count();

        $teamInvestorBonus = match (true) {
            $activeDirectInvestors >= 5 => 0.0100,
            $activeDirectInvestors >= 3 => 0.0050,
            $activeDirectInvestors >= 1 => 0.0025,
            default => 0.0000,
        };

        return round(self::invitationBonusRate($user) + $teamInvestorBonus, 4);
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

    public static function starterUpgradeProgress(User $user): array
    {
        $verifiedInvites = $user->friendInvitations()->whereNotNull('verified_at')->count();
        $directBasicSubscribers = UserInvestment::query()
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->whereHas('user', fn ($query) => $query->where('sponsor_user_id', $user->id))
            ->whereHas('package', fn ($query) => $query->where('slug', self::BASIC_UPGRADE_PACKAGE_SLUG))
            ->count();
        $hasFreeStarter = $user->investments()
            ->whereHas('package', fn ($query) => $query->where('slug', self::FREE_STARTER_PACKAGE_SLUG))
            ->exists();
        $hasUnlockedBasic = $user->investments()
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->whereHas('package', fn ($query) => $query->where('slug', self::BASIC_UPGRADE_PACKAGE_SLUG))
            ->exists();

        return [
            'verified_invites' => $verifiedInvites,
            'required_verified_invites' => (int) self::rewardSetting('free_starter_verified_invites_required'),
            'direct_basic_subscribers' => $directBasicSubscribers,
            'required_direct_basic_subscribers' => (int) self::rewardSetting('free_starter_direct_basic_required'),
            'has_free_starter' => $hasFreeStarter,
            'has_unlocked_basic' => $hasUnlockedBasic,
            'qualifies' => $verifiedInvites >= (int) self::rewardSetting('free_starter_verified_invites_required') && $directBasicSubscribers >= (int) self::rewardSetting('free_starter_direct_basic_required'),
        ];
    }

    public static function attemptStarterUpgrade(User $user): ?UserInvestment
    {
        self::ensureDefaults();

        $progress = self::starterUpgradeProgress($user);

        if (! $progress['qualifies'] || $progress['has_unlocked_basic']) {
            return null;
        }

        $package = self::basicUpgradePackage();
        $level = self::syncUserLevel($user);
        $teamBonusRate = self::teamBonusRate($user);

        $shareholder = Shareholder::updateOrCreate(
            ['user_id' => $user->id],
            [
                'package_name' => $package->name,
                'price' => $package->price,
                'billing_cycle' => 'monthly',
                'units_limit' => $package->units_limit,
                'status' => 'active',
                'subscribed_at' => now(),
            ],
        );

        $investment = UserInvestment::create([
            'user_id' => $user->id,
            'miner_id' => $package->miner_id,
            'package_id' => $package->id,
            'shareholder_id' => $shareholder->id,
            'amount' => $package->price,
            'shares_owned' => $package->shares_count,
            'monthly_return_rate' => $package->monthly_return_rate,
            'level_bonus_rate' => $level->bonus_rate,
            'team_bonus_rate' => $teamBonusRate,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        $user->forceFill(['account_type' => 'shareholder'])->save();
        self::refreshInvestmentBonusRates($user->fresh());
        $investment->refresh();

        Earning::firstOrCreate(
            [
                'user_id' => $user->id,
                'investment_id' => $investment->id,
                'earned_on' => now()->toDateString(),
                'source' => 'projected_return',
                'notes' => 'Starter upgrade unlocked '.$package->name.' automatically.',
            ],
            [
                'amount' => round((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate), 2),
                'status' => 'pending',
            ],
        );

        self::recordReferralEvent(
            $user,
            'starter_upgrade_unlocked',
            'Free starter upgraded',
            'You unlocked '.$package->name.' after completing the referral mission.',
            $user,
            $investment,
        );

        return $investment;
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

        return $user->investments()
            ->with('package')
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->get()
            ->map(function (UserInvestment $investment) use ($user, $period) {
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
                ['amount' => (float) self::rewardSetting('referral_registration_reward'), 'status' => 'available'],
            );
        });
    }

    public static function awardReferralSubscription(User $referredUser, UserInvestment $investment): Collection
    {
        $rewardAmount = round((float) $investment->amount * (float) self::rewardSetting('referral_subscription_reward_rate'), 2);

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
        $depth = 1;
        $currentSponsor = $referredUser->sponsor()->first();

        while ($currentSponsor && $depth <= 5) {
            $rewardRate = self::networkLevelRewardRate($depth);

            if ($rewardRate > 0) {
                $rewardAmount = round((float) $investment->amount * $rewardRate, 2);
                $source = match ($depth) {
                    1 => 'team_subscription_bonus',
                    2 => 'team_downline_bonus',
                    default => 'team_level_'.$depth.'_bonus',
                };
                $notes = match ($depth) {
                    1 => 'Team subscription bonus for '.$referredUser->email.' on investment #'.$investment->id.'.',
                    2 => 'Second-level team bonus for '.$referredUser->email.' on investment #'.$investment->id.'.',
                    default => 'Level '.$depth.' team bonus for '.$referredUser->email.' on investment #'.$investment->id.'.',
                };
                $title = match ($depth) {
                    1 => 'A team investor subscribed',
                    2 => 'A second-level investor subscribed',
                    default => 'A level '.$depth.' investor subscribed',
                };
                $message = match ($depth) {
                    1 => $referredUser->name.' subscribed to '.$investment->package?->name.' under your team.',
                    2 => $referredUser->name.' subscribed in your extended network.',
                    default => $referredUser->name.' subscribed in level '.$depth.' of your network.',
                };
                $type = match ($depth) {
                    1 => 'team_subscription',
                    2 => 'team_downline_subscription',
                    default => 'team_level_'.$depth.'_subscription',
                };

                Earning::firstOrCreate(
                    [
                        'user_id' => $currentSponsor->id,
                        'investment_id' => null,
                        'earned_on' => now()->toDateString(),
                        'source' => $source,
                        'notes' => $notes,
                    ],
                    [
                        'amount' => $rewardAmount,
                        'status' => 'available',
                    ],
                );

                self::recordReferralEvent(
                    $currentSponsor,
                    $type,
                    $title,
                    $message,
                    $referredUser,
                    $investment,
                );
            }

            if ($depth === 1) {
                self::refreshInvestmentBonusRates($currentSponsor->fresh());
                self::attemptStarterUpgrade($currentSponsor->fresh());
            }

            $currentSponsor = $currentSponsor->sponsor()->first();
            $depth++;
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
            [
                'name' => self::platformSetting('launch_package_name'),
                'suffix' => 'launch',
                'shares_count' => (int) self::platformSetting('launch_package_shares_count'),
                'units_limit' => (int) self::platformSetting('launch_package_units_limit'),
                'price_multiplier' => (float) self::platformSetting('launch_package_price_multiplier'),
                'rate_bonus' => (float) self::platformSetting('launch_package_rate_bonus'),
                'display_order' => 1,
            ],
            [
                'name' => self::platformSetting('growth_package_name'),
                'suffix' => 'growth',
                'shares_count' => (int) self::platformSetting('growth_package_shares_count'),
                'units_limit' => (int) self::platformSetting('growth_package_units_limit'),
                'price_multiplier' => (float) self::platformSetting('growth_package_price_multiplier'),
                'rate_bonus' => (float) self::platformSetting('growth_package_rate_bonus'),
                'display_order' => 2,
            ],
            [
                'name' => self::platformSetting('scale_package_name'),
                'suffix' => 'scale',
                'shares_count' => (int) self::platformSetting('scale_package_shares_count'),
                'units_limit' => (int) self::platformSetting('scale_package_units_limit'),
                'price_multiplier' => (float) self::platformSetting('scale_package_price_multiplier'),
                'rate_bonus' => (float) self::platformSetting('scale_package_rate_bonus'),
                'display_order' => 3,
            ],
        ];

        foreach ($definitions as $definition) {
            $price = round((float) $miner->share_price * max($definition['price_multiplier'], 0), 2);
            $sharesCount = max($definition['shares_count'], 1);
            $unitsLimit = max($definition['units_limit'], 1);

            InvestmentPackage::updateOrCreate(
                ['slug' => $miner->slug.'-'.$definition['suffix']],
                [
                    'miner_id' => $miner->id,
                    'name' => $definition['name'].' '.number_format($price, 0, '.', ''),
                    'price' => $price,
                    'shares_count' => $sharesCount,
                    'units_limit' => $unitsLimit,
                    'monthly_return_rate' => round((float) $miner->base_monthly_return_rate + max($definition['rate_bonus'], 0), 4),
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








