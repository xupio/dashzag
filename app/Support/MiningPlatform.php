<?php

namespace App\Support;

use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\PayoutRequest;
use App\Models\PlatformSetting;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\UserLevel;
use App\Notifications\ActivityFeedNotification;
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
            'payout_btc_wallet_enabled' => '1',
            'payout_btc_wallet_label' => 'BTC Wallet',
            'payout_btc_wallet_placeholder' => 'Enter your BTC wallet address',
            'payout_btc_wallet_minimum_amount' => '25',
            'payout_btc_wallet_fixed_fee' => '0',
            'payout_btc_wallet_percentage_fee_rate' => '0',
            'payout_btc_wallet_instruction' => 'Make sure your BTC address is correct before submitting.',
            'payout_btc_wallet_processing_time' => 'Within 24 hours',
            'payout_usdt_wallet_enabled' => '1',
            'payout_usdt_wallet_label' => 'USDT Wallet',
            'payout_usdt_wallet_placeholder' => 'Enter your USDT wallet address',
            'payout_usdt_wallet_minimum_amount' => '25',
            'payout_usdt_wallet_fixed_fee' => '0',
            'payout_usdt_wallet_percentage_fee_rate' => '0',
            'payout_usdt_wallet_instruction' => 'Use the correct network for your USDT wallet destination.',
            'payout_usdt_wallet_processing_time' => 'Within 12 hours',
            'payout_bank_transfer_enabled' => '1',
            'payout_bank_transfer_label' => 'Bank Transfer',
            'payout_bank_transfer_placeholder' => 'Enter your bank account or IBAN details',
            'payout_bank_transfer_minimum_amount' => '100',
            'payout_bank_transfer_fixed_fee' => '15',
            'payout_bank_transfer_percentage_fee_rate' => '0',
            'payout_bank_transfer_instruction' => 'Include full beneficiary and banking details to avoid delays.',
            'payout_bank_transfer_processing_time' => '2 to 5 business days',
            'payment_btc_transfer_enabled' => '1',
            'payment_btc_transfer_label' => 'BTC Transfer',
            'payment_btc_transfer_destination' => 'Set your BTC receiving wallet in admin settings.',
            'payment_btc_transfer_reference_hint' => 'Paste the BTC transaction hash after sending payment.',
            'payment_btc_transfer_instruction' => 'Send the exact package amount to the BTC wallet, then submit the transaction hash for review.',
            'payment_btc_transfer_admin_review_note' => 'Confirm the wallet matches the active BTC treasury address and compare the hash amount with the selected package.',
            'payment_usdt_transfer_enabled' => '1',
            'payment_usdt_transfer_label' => 'USDT Transfer',
            'payment_usdt_transfer_destination' => 'Set your USDT receiving wallet in admin settings.',
            'payment_usdt_transfer_reference_hint' => 'Paste the USDT transaction hash and use the correct network.',
            'payment_usdt_transfer_instruction' => 'Send the exact package amount to the configured USDT wallet and make sure the network matches the wallet details.',
            'payment_usdt_transfer_admin_review_note' => 'Verify the network matches the configured wallet and confirm the hash amount before approval.',
            'payment_bank_transfer_enabled' => '1',
            'payment_bank_transfer_label' => 'Bank Transfer',
            'payment_bank_transfer_destination' => 'Set your bank beneficiary details in admin settings.',
            'payment_bank_transfer_reference_hint' => 'Enter the bank transfer reference, receipt number, or SWIFT trace.',
            'payment_bank_transfer_instruction' => 'Transfer the package amount to the listed beneficiary account, then submit the bank reference for manual review.',
            'payment_bank_transfer_admin_review_note' => 'Match the beneficiary details, receipt date, and reference number before marking the order approved.',
            'notification_payout_in_app' => '1',
            'notification_payout_email' => '1',
            'notification_reward_in_app' => '1',
            'notification_reward_email' => '0',
            'notification_investment_in_app' => '1',
            'notification_investment_email' => '1',
            'notification_network_in_app' => '1',
            'notification_network_email' => '0',
            'notification_milestone_in_app' => '1',
            'notification_milestone_email' => '0',
            'template_payout_submitted_subject' => 'Payout Request Submitted',
            'template_payout_submitted_message' => 'Your payout request has been submitted and is now waiting for review.',
            'template_payout_approved_subject' => 'Payout Request Approved',
            'template_payout_approved_message' => 'Your payout request has been approved by the operations team.',
            'template_payout_paid_subject' => 'Payout Request Paid',
            'template_payout_paid_message' => 'Your payout request has been marked as paid.',
            'template_free_starter_subject' => 'Free Starter activated',
            'template_free_starter_message' => 'Your account now includes the Starter Free package and is ready for referral progress.',
            'template_network_join_subject' => 'A referred user joined your network',
            'template_network_join_message' => ':user_name completed email verification and is now attached to your team.',
            'template_reward_registration_subject' => 'Referral registration reward added',
            'template_reward_registration_message' => ':user_name completed registration and your referral reward is now available in the wallet.',
            'template_network_sponsor_subject' => 'You are now linked to a sponsor team',
            'template_network_sponsor_message' => 'Your account has been connected to :sponsor_name after confirming your email.',
            'template_basic_unlocked_subject' => 'Basic 100 unlocked',
            'template_basic_unlocked_message' => 'Your Starter Free mission is complete and the Basic 100 package is now active on your account.',
            'template_investment_payment_submitted_subject' => 'Investment payment submitted',
            'template_investment_payment_submitted_message' => 'Your payment for :package_name has been submitted and is now waiting for admin review.',
            'template_investment_payment_proof_subject' => 'Payment proof uploaded',
            'template_investment_payment_proof_message' => 'Your payment proof for :package_name has been uploaded successfully and is now waiting for admin review.',
            'template_investment_payment_approved_subject' => 'Investment payment approved',
            'template_investment_payment_approved_message' => 'Your payment for :package_name has been approved and your subscription is now being activated.',
            'template_investment_payment_override_subject' => 'Investment approved without proof override',
            'template_investment_payment_override_message' => 'Your :package_name order was approved using an admin override before a payment proof was uploaded.',
            'template_investment_payment_rejected_subject' => 'Investment payment rejected',
            'template_investment_payment_rejected_message' => 'Your payment for :package_name was rejected. Please review the admin notes and resubmit with the correct payment reference.',
            'template_investment_activated_subject' => 'Investment subscription activated',
            'template_investment_activated_message' => 'Your :package_name package is active and your mining shares are now running.',
            'template_team_level_1_subject' => 'Direct referral investment reward added',
            'template_team_level_1_message' => ':user_name subscribed to :package_name and your direct reward plus team bonus have been added.',
            'template_team_level_2_subject' => 'A second-level investor subscribed',
            'template_team_level_2_message' => ':user_name subscribed in your extended network.',
            'template_team_level_generic_subject' => 'A level :level investor subscribed',
            'template_team_level_generic_message' => ':user_name subscribed in level :level of your network.',
        ];
    }

    public static function defaultNotificationTemplateSettings(): array
    {
        return [
            'template_payout_submitted_subject' => 'Payout Request Submitted',
            'template_payout_submitted_message' => 'Your payout request has been submitted and is now waiting for review.',
            'template_payout_approved_subject' => 'Payout Request Approved',
            'template_payout_approved_message' => 'Your payout request has been approved by the operations team.',
            'template_payout_paid_subject' => 'Payout Request Paid',
            'template_payout_paid_message' => 'Your payout request has been marked as paid.',
            'template_free_starter_subject' => 'Free Starter activated',
            'template_free_starter_message' => 'Your account now includes the Starter Free package and is ready for referral progress.',
            'template_network_join_subject' => 'A referred user joined your network',
            'template_network_join_message' => ':user_name completed email verification and is now attached to your team.',
            'template_reward_registration_subject' => 'Referral registration reward added',
            'template_reward_registration_message' => ':user_name completed registration and your referral reward is now available in the wallet.',
            'template_network_sponsor_subject' => 'You are now linked to a sponsor team',
            'template_network_sponsor_message' => 'Your account has been connected to :sponsor_name after confirming your email.',
            'template_basic_unlocked_subject' => 'Basic 100 unlocked',
            'template_basic_unlocked_message' => 'Your Starter Free mission is complete and the Basic 100 package is now active on your account.',
            'template_investment_payment_submitted_subject' => 'Investment payment submitted',
            'template_investment_payment_submitted_message' => 'Your payment for :package_name has been submitted and is now waiting for admin review.',
            'template_investment_payment_proof_subject' => 'Payment proof uploaded',
            'template_investment_payment_proof_message' => 'Your payment proof for :package_name has been uploaded successfully and is now waiting for admin review.',
            'template_investment_payment_approved_subject' => 'Investment payment approved',
            'template_investment_payment_approved_message' => 'Your payment for :package_name has been approved and your subscription is now being activated.',
            'template_investment_payment_override_subject' => 'Investment approved without proof override',
            'template_investment_payment_override_message' => 'Your :package_name order was approved using an admin override before a payment proof was uploaded.',
            'template_investment_payment_rejected_subject' => 'Investment payment rejected',
            'template_investment_payment_rejected_message' => 'Your payment for :package_name was rejected. Please review the admin notes and resubmit with the correct payment reference.',
            'template_investment_activated_subject' => 'Investment subscription activated',
            'template_investment_activated_message' => 'Your :package_name package is active and your mining shares are now running.',
            'template_team_level_1_subject' => 'Direct referral investment reward added',
            'template_team_level_1_message' => ':user_name subscribed to :package_name and your direct reward plus team bonus have been added.',
            'template_team_level_2_subject' => 'A second-level investor subscribed',
            'template_team_level_2_message' => ':user_name subscribed in your extended network.',
            'template_team_level_generic_subject' => 'A level :level investor subscribed',
            'template_team_level_generic_message' => ':user_name subscribed in level :level of your network.',
        ];
    }

    public static function notificationTemplateSetting(string $key): string
    {
        return self::platformSettings()[$key] ?? self::defaultNotificationTemplateSettings()[$key] ?? '';
    }

    public static function renderNotificationTemplate(string $template, array $replacements = []): string
    {
        $rendered = $template;

        foreach ($replacements as $key => $value) {
            $rendered = str_replace(':'.$key, (string) $value, $rendered);
        }

        return $rendered;
    }

    public static function activityTemplate(string $key, array $replacements = []): array
    {
        return [
            'subject' => self::renderNotificationTemplate(self::notificationTemplateSetting('template_'.$key.'_subject'), $replacements),
            'message' => self::renderNotificationTemplate(self::notificationTemplateSetting('template_'.$key.'_message'), $replacements),
        ];
    }
    public static function digestSummaryForUser(User $user, ?string $frequency = null): array
    {
        $resolvedFrequency = in_array($frequency, ['daily', 'weekly'], true) ? $frequency : $user->digestFrequency();
        $start = $resolvedFrequency === 'daily' ? now()->subDay() : now()->subWeek();
        $notifications = $user->notifications()->where('created_at', '>=', $start)->get();
        $trackedCategories = ['payout', 'reward', 'investment', 'network', 'milestone'];

        $summary = [
            'frequency' => $resolvedFrequency,
            'period_label' => $resolvedFrequency === 'daily' ? 'the last 24 hours' : 'the last 7 days',
            'total' => 0,
            'unread' => (int) $notifications->whereNull('read_at')->count(),
        ];

        foreach ($trackedCategories as $category) {
            $summary[$category] = (int) $notifications->filter(
                fn ($notification) => ($notification->data['category'] ?? 'payout') === $category
            )->count();
            $summary['total'] += $summary[$category];
        }

        return $summary;
    }
    public static function notificationDefaultPreferences(): array
    {
        return [
            'payout' => [
                'in_app' => self::platformSetting('notification_payout_in_app') === '1',
                'email' => self::platformSetting('notification_payout_email') === '1',
            ],
            'reward' => [
                'in_app' => self::platformSetting('notification_reward_in_app') === '1',
                'email' => self::platformSetting('notification_reward_email') === '1',
            ],
            'investment' => [
                'in_app' => self::platformSetting('notification_investment_in_app') === '1',
                'email' => self::platformSetting('notification_investment_email') === '1',
            ],
            'network' => [
                'in_app' => self::platformSetting('notification_network_in_app') === '1',
                'email' => self::platformSetting('notification_network_email') === '1',
            ],
            'milestone' => [
                'in_app' => self::platformSetting('notification_milestone_in_app') === '1',
                'email' => self::platformSetting('notification_milestone_email') === '1',
            ],
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


    public static function payoutMethods(): array
    {
        return [
            [
                'key' => 'btc_wallet',
                'label' => self::platformSetting('payout_btc_wallet_label'),
                'placeholder' => self::platformSetting('payout_btc_wallet_placeholder'),
                'enabled' => self::platformSetting('payout_btc_wallet_enabled') === '1',
                'minimum_amount' => (float) self::platformSetting('payout_btc_wallet_minimum_amount'),
                'fixed_fee' => (float) self::platformSetting('payout_btc_wallet_fixed_fee'),
                'percentage_fee_rate' => (float) self::platformSetting('payout_btc_wallet_percentage_fee_rate'),
                'instruction' => self::platformSetting('payout_btc_wallet_instruction'),
                'processing_time' => self::platformSetting('payout_btc_wallet_processing_time'),
            ],
            [
                'key' => 'usdt_wallet',
                'label' => self::platformSetting('payout_usdt_wallet_label'),
                'placeholder' => self::platformSetting('payout_usdt_wallet_placeholder'),
                'enabled' => self::platformSetting('payout_usdt_wallet_enabled') === '1',
                'minimum_amount' => (float) self::platformSetting('payout_usdt_wallet_minimum_amount'),
                'fixed_fee' => (float) self::platformSetting('payout_usdt_wallet_fixed_fee'),
                'percentage_fee_rate' => (float) self::platformSetting('payout_usdt_wallet_percentage_fee_rate'),
                'instruction' => self::platformSetting('payout_usdt_wallet_instruction'),
                'processing_time' => self::platformSetting('payout_usdt_wallet_processing_time'),
            ],
            [
                'key' => 'bank_transfer',
                'label' => self::platformSetting('payout_bank_transfer_label'),
                'placeholder' => self::platformSetting('payout_bank_transfer_placeholder'),
                'enabled' => self::platformSetting('payout_bank_transfer_enabled') === '1',
                'minimum_amount' => (float) self::platformSetting('payout_bank_transfer_minimum_amount'),
                'fixed_fee' => (float) self::platformSetting('payout_bank_transfer_fixed_fee'),
                'percentage_fee_rate' => (float) self::platformSetting('payout_bank_transfer_percentage_fee_rate'),
                'instruction' => self::platformSetting('payout_bank_transfer_instruction'),
                'processing_time' => self::platformSetting('payout_bank_transfer_processing_time'),
            ],
        ];
    }

    public static function activePayoutMethods(): array
    {
        return array_values(array_filter(self::payoutMethods(), fn (array $method) => $method['enabled']));
    }

    public static function payoutMethodKeys(): array
    {
        return array_column(self::activePayoutMethods(), 'key');
    }

    public static function payoutMethodLabel(string $key): string
    {
        foreach (self::payoutMethods() as $method) {
            if ($method['key'] === $key) {
                return $method['label'];
            }
        }

        return str($key)->replace('_', ' ')->title()->toString();
    }

    public static function payoutMethod(string $key): ?array
    {
        foreach (self::payoutMethods() as $method) {
            if ($method['key'] === $key) {
                return $method;
            }
        }

        return null;
    }

    public static function payoutQuote(string $key, float $amount): array
    {
        $method = self::payoutMethod($key);

        if (! $method) {
            return [
                'minimum_amount' => 0.0,
                'fixed_fee' => 0.0,
                'percentage_fee_rate' => 0.0,
                'fee_amount' => 0.0,
                'net_amount' => max(round($amount, 2), 0),
            ];
        }

        $grossAmount = round(max($amount, 0), 2);
        $feeAmount = round((float) $method['fixed_fee'] + ($grossAmount * (float) $method['percentage_fee_rate']), 2);
        $netAmount = round(max($grossAmount - $feeAmount, 0), 2);

        return [
            'minimum_amount' => (float) $method['minimum_amount'],
            'fixed_fee' => (float) $method['fixed_fee'],
            'percentage_fee_rate' => (float) $method['percentage_fee_rate'],
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'instruction' => $method['instruction'],
            'processing_time' => $method['processing_time'],
            'placeholder' => $method['placeholder'],
            'label' => $method['label'],
        ];
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

    public static function submitInvestmentOrder(User $user, InvestmentPackage $package, array $paymentData): InvestmentOrder
    {
        return InvestmentOrder::create([
            'user_id' => $user->id,
            'miner_id' => $package->miner_id,
            'package_id' => $package->id,
            'amount' => $package->price,
            'shares_owned' => $package->shares_count,
            'payment_method' => $paymentData['payment_method'],
            'payment_reference' => $paymentData['payment_reference'],
            'payment_proof_path' => $paymentData['payment_proof_path'] ?? null,
            'payment_proof_original_name' => $paymentData['payment_proof_original_name'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'status' => 'pending',
            'submitted_at' => now(),
            'proof_uploaded_at' => isset($paymentData['payment_proof_path']) ? now() : null,
        ]);
    }


    public static function rejectInvestmentOrder(InvestmentOrder $order, User $admin, ?string $adminNotes = null): InvestmentOrder
    {
        if ($order->status !== 'pending') {
            throw new RuntimeException('Only pending investment orders can be rejected.');
        }

        $order->loadMissing(['user', 'package']);

        $order->forceFill([
            'status' => 'rejected',
            'approved_by_id' => $admin->id,
            'admin_notes' => $adminNotes,
            'rejected_at' => now(),
        ])->save();

        if ($order->user) {
            $rejectionTemplate = self::activityTemplate('investment_payment_rejected', [
                'package_name' => $order->package?->name ?? 'the selected package',
            ]);

            $order->user->notify(new ActivityFeedNotification([
                'category' => 'investment',
                'status' => 'danger',
                'subject' => $rejectionTemplate['subject'],
                'message' => $rejectionTemplate['message'],
                'context_label' => 'Admin notes',
                'context_value' => $adminNotes ?: 'No extra notes provided.',
                'amount' => (float) $order->amount,
                'amount_label' => 'Submitted amount',
                'force_mail' => true,
            ]));
        }

        return $order->fresh(['user', 'package', 'miner', 'approver']);
    }
    public static function approveInvestmentOrder(InvestmentOrder $order, User $admin, bool $allowWithoutProof = false, ?string $adminNotes = null): UserInvestment
    {
        if ($order->status !== 'pending') {
            throw new RuntimeException('Only pending investment orders can be approved.');
        }

        if (! $order->payment_proof_path && ! $allowWithoutProof) {
            throw new RuntimeException('Payment proof is required before approval.');
        }

        if (! $order->payment_proof_path && blank($adminNotes)) {
            throw new RuntimeException('Admin notes are required when approving without proof.');
        }

        return DB::transaction(function () use ($order, $admin, $adminNotes) {
            $order->loadMissing(['user.sponsor', 'package.miner']);

            if (! $order->user || ! $order->package) {
                throw new RuntimeException('Investment order is missing its related user or package.');
            }

            $user = $order->user;
            $package = $order->package;
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
            $refreshedUser = self::refreshInvestmentBonusRates($user->fresh());
            $investment->refresh();

            Earning::firstOrCreate(
                [
                    'user_id' => $refreshedUser->id,
                    'investment_id' => $investment->id,
                    'earned_on' => now()->toDateString(),
                    'source' => 'projected_return',
                ],
                [
                    'amount' => round((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate), 2),
                    'status' => 'pending',
                    'notes' => 'Initial projected monthly return generated after investment order approval.',
                ],
            );

            self::awardReferralSubscription($refreshedUser, $investment);

            $order->forceFill([
                'status' => 'approved',
                'approved_by_id' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $adminNotes ?: $order->admin_notes,
            ])->save();

            return $investment;
        });
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

    public static function performanceSnapshotForDate(Miner $miner, Carbon|string|null $date = null): array
    {
        $day = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date ?? now())->startOfDay();
        $seed = abs(crc32($miner->slug.'|'.$day->toDateString()));
        $uptime = round(min(99.95, max(90, 95 + (($seed % 420) / 100))), 2);
        $hashrateBase = max(((float) $miner->total_shares * 0.48), 1);
        $hashrateMultiplier = 0.92 + (((($seed >> 4)) % 18) / 100);
        $marketMultiplier = 0.94 + (((($seed >> 9)) % 15) / 100);
        $dailyOutput = max((float) $miner->daily_output_usd, 0);
        $revenue = round($dailyOutput * ($uptime / 100) * $marketMultiplier, 2);
        $electricityRate = 0.16 + (((($seed >> 13)) % 5) / 100);
        $maintenanceRate = 0.05 + (((($seed >> 17)) % 4) / 100);
        $electricityCost = round($revenue * $electricityRate, 2);
        $maintenanceCost = round($revenue * $maintenanceRate, 2);
        $netProfit = round(max($revenue - $electricityCost - $maintenanceCost, 0), 2);
        $activeShares = max(self::totalSharesSold($miner), 0);
        $revenuePerShare = $activeShares > 0 ? round($netProfit / $activeShares, 4) : 0;

        return [
            'logged_on' => $day->toDateString(),
            'revenue_usd' => $revenue,
            'electricity_cost_usd' => $electricityCost,
            'maintenance_cost_usd' => $maintenanceCost,
            'net_profit_usd' => $netProfit,
            'hashrate_th' => round($hashrateBase * $hashrateMultiplier, 2),
            'uptime_percentage' => $uptime,
            'active_shares' => $activeShares,
            'revenue_per_share_usd' => $revenuePerShare,
            'source' => 'automatic',
            'auto_generated_at' => now(),
            'notes' => 'Automatic daily miner snapshot generated from baseline output, uptime, and operating cost formulas.',
        ];
    }

    public static function savePerformanceLog(Miner $miner, array $attributes, string $source = 'manual'): MinerPerformanceLog
    {
        $loggedOn = Carbon::parse($attributes['logged_on'] ?? now())->toDateString();
        $revenue = round((float) ($attributes['revenue_usd'] ?? 0), 2);
        $electricityCost = round((float) ($attributes['electricity_cost_usd'] ?? ($revenue * 0.18)), 2);
        $maintenanceCost = round((float) ($attributes['maintenance_cost_usd'] ?? ($revenue * 0.06)), 2);
        $netProfit = round(max((float) ($attributes['net_profit_usd'] ?? ($revenue - $electricityCost - $maintenanceCost)), 0), 2);
        $activeShares = max((int) ($attributes['active_shares'] ?? self::totalSharesSold($miner)), 0);
        $revenuePerShare = $activeShares > 0
            ? round((float) ($attributes['revenue_per_share_usd'] ?? ($netProfit / $activeShares)), 4)
            : 0;

        $log = MinerPerformanceLog::updateOrCreate(
            [
                'miner_id' => $miner->id,
                'logged_on' => $loggedOn,
            ],
            [
                'revenue_usd' => $revenue,
                'electricity_cost_usd' => $electricityCost,
                'maintenance_cost_usd' => $maintenanceCost,
                'net_profit_usd' => $netProfit,
                'hashrate_th' => round((float) ($attributes['hashrate_th'] ?? 0), 2),
                'uptime_percentage' => round((float) ($attributes['uptime_percentage'] ?? 0), 2),
                'active_shares' => $activeShares,
                'revenue_per_share_usd' => $revenuePerShare,
                'source' => $source,
                'auto_generated_at' => $source === 'automatic' ? now() : null,
                'notes' => $attributes['notes'] ?? null,
            ],
        );

        self::distributeDailyPerformanceEarnings($log);

        return $log;
    }

    public static function generateAutomaticPerformanceLog(Miner $miner, Carbon|string|null $date = null): MinerPerformanceLog
    {
        return self::savePerformanceLog($miner, self::performanceSnapshotForDate($miner, $date), 'automatic');
    }

    public static function distributeDailyPerformanceEarnings(MinerPerformanceLog $log): Collection
    {
        $log->loadMissing('miner');

        return UserInvestment::query()
            ->with(['user', 'package'])
            ->where('miner_id', $log->miner_id)
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->get()
            ->map(function (UserInvestment $investment) use ($log) {
                $amount = round((float) $investment->shares_owned * (float) $log->revenue_per_share_usd, 2);

                return Earning::updateOrCreate(
                    [
                        'user_id' => $investment->user_id,
                        'investment_id' => $investment->id,
                        'earned_on' => $log->logged_on->toDateString(),
                        'source' => 'mining_daily_share',
                    ],
                    [
                        'amount' => $amount,
                        'status' => 'available',
                        'notes' => 'Daily miner distribution from '.$log->miner->name.' on '.$log->logged_on->format('Y-m-d').' at $'.number_format((float) $log->revenue_per_share_usd, 4).' per share.',
                    ],
                );
            });
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

            $quote = self::payoutQuote($method, $amount);
            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'fee_amount' => $quote['fee_amount'],
                'net_amount' => $quote['net_amount'],
                'fee_rate' => $quote['percentage_fee_rate'],
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

            InvestmentPackage::firstOrCreate(
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
        $miner = Miner::firstOrCreate(
            ['slug' => $minerData['slug']],
            $minerData,
        );

        foreach ($packages as $package) {
            InvestmentPackage::firstOrCreate(
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
            $revenue = round($revenueBase + ($offset * $revenueStep), 2);
            $electricityCost = round($revenue * 0.18, 2);
            $maintenanceCost = round($revenue * 0.06, 2);
            $netProfit = round(max($revenue - $electricityCost - $maintenanceCost, 0), 2);
            $activeShares = max(self::totalSharesSold($miner), 0);
            $revenuePerShare = $activeShares > 0 ? round($netProfit / $activeShares, 4) : 0;

            DB::table('miner_performance_logs')->updateOrInsert(
                ['miner_id' => $miner->id, 'logged_on' => $date],
                [
                    'revenue_usd' => $revenue,
                    'electricity_cost_usd' => $electricityCost,
                    'maintenance_cost_usd' => $maintenanceCost,
                    'net_profit_usd' => $netProfit,
                    'hashrate_th' => round($hashrateBase + ($offset * $hashrateStep), 2),
                    'uptime_percentage' => round($uptimeBase + ($offset * $uptimeStep), 2),
                    'active_shares' => $activeShares,
                    'revenue_per_share_usd' => $revenuePerShare,
                    'source' => 'seeded',
                    'auto_generated_at' => now(),
                    'notes' => 'Auto-generated baseline log for dashboard visibility.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
































