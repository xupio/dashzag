<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\HallOfFameSnapshot;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MiningPlatform
{
    protected static bool $defaultsEnsuredForRequest = false;

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
            'profile_power_basic_max_rate' => '0.0400',
            'profile_power_growth_max_rate' => '0.0600',
            'profile_power_scale_max_rate' => '0.0700',
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

    public static function adminHealthSummary(?Carbon $since = null): array
    {
        $since ??= now()->subDay();

        $pendingInvestmentOrders = InvestmentOrder::query()->where('status', 'pending');
        $pendingPayoutRequests = PayoutRequest::query()->where('status', 'pending');

        return [
            'period_label' => 'the last 24 hours',
            'pending_investment_orders' => (clone $pendingInvestmentOrders)->count(),
            'pending_payout_requests' => (clone $pendingPayoutRequests)->count(),
            'pending_orders_with_proof' => (clone $pendingInvestmentOrders)->whereNotNull('payment_proof_path')->count(),
            'pending_orders_missing_proof' => (clone $pendingInvestmentOrders)->whereNull('payment_proof_path')->count(),
            'stale_pending_investments' => (clone $pendingInvestmentOrders)->where('submitted_at', '<', $since)->count(),
            'stale_pending_payouts' => (clone $pendingPayoutRequests)->where('requested_at', '<', $since)->count(),
            'recent_admin_actions' => AdminActivityLog::query()->where('created_at', '>=', $since)->count(),
            'pending_friend_invitations' => FriendInvitation::query()->whereNull('verified_at')->count(),
        ];
    }

    public static function notifyAdminsOfCriticalAlert(
        string $subject,
        string $message,
        ?string $statusLine = null,
        ?string $notesLine = null,
        ?string $contextLabel = null,
        ?string $contextValue = null,
        array $details = [],
    ): void {
        User::query()
            ->where('role', 'admin')
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->get()
            ->each(function (User $admin) use ($subject, $message, $statusLine, $notesLine, $contextLabel, $contextValue, $details) {
                $admin->notify(new ActivityFeedNotification([
                    'category' => 'admin',
                    'status' => 'warning',
                    'subject' => $subject,
                    'message' => $message,
                    'status_line' => $statusLine,
                    'notes_line' => $notesLine,
                    'context_label' => $contextLabel,
                    'context_value' => $contextValue,
                    'force_mail' => true,
                    'details' => $details,
                ]));
            });
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

    public static function investmentProfilePowerRewardCap(float $amount): float
    {
        return match (true) {
            $amount >= 1000 => (float) self::rewardSetting('profile_power_scale_max_rate'),
            $amount >= 500 => (float) self::rewardSetting('profile_power_growth_max_rate'),
            $amount > 0 => (float) self::rewardSetting('profile_power_basic_max_rate'),
            default => 0.0000,
        };
    }

    public static function investmentProfilePowerRewardRate(UserInvestment $investment): float
    {
        $investment->loadMissing(['user.userLevel', 'user.friendInvitations', 'user.investments', 'user.sponsoredUsers.investments']);

        if ((float) $investment->amount <= 0 || ! $investment->user) {
            return 0.0000;
        }

        return self::investmentProfilePowerRewardRateForScore(
            (float) $investment->amount,
            (float) (self::profilePowerSummary($investment->user)['score'] ?? 0),
        );
    }

    public static function investmentProfilePowerRewardRateForScore(float $amount, float $powerScore): float
    {
        if ($amount <= 0) {
            return 0.0000;
        }

        $maxRate = self::investmentProfilePowerRewardCap($amount);

        return round($maxRate * min(max($powerScore, 0), 100) / 100, 4);
    }

    public static function invitationBonusRateForCount(int $verifiedInvites): float
    {
        return match (true) {
            $verifiedInvites >= 50 => (float) self::rewardSetting('invitation_bonus_after_50_rate'),
            $verifiedInvites >= 20 => (float) self::rewardSetting('invitation_bonus_after_20_rate'),
            $verifiedInvites >= 10 => (float) self::rewardSetting('invitation_bonus_after_10_rate'),
            default => 0.0000,
        };
    }

    public static function teamInvestorBonusRateForCount(int $activeDirectInvestors): float
    {
        return match (true) {
            $activeDirectInvestors >= 5 => (float) self::rewardSetting('team_bonus_after_5_investor_rate'),
            $activeDirectInvestors >= 3 => (float) self::rewardSetting('team_bonus_after_3_investor_rate'),
            $activeDirectInvestors >= 1 => (float) self::rewardSetting('team_bonus_after_1_investor_rate'),
            default => 0.0000,
        };
    }

    public static function teamBonusRateForCounts(int $verifiedInvites, int $activeDirectInvestors): float
    {
        return round(
            self::invitationBonusRateForCount($verifiedInvites) + self::teamInvestorBonusRateForCount($activeDirectInvestors),
            4
        );
    }

    public static function resolveUserLevelForMetrics(int $registeredReferrals, float $totalInvestment): UserLevel
    {
        self::ensureDefaults();

        return UserLevel::query()
            ->orderByDesc('rank')
            ->get()
            ->first(function (UserLevel $candidate) use ($registeredReferrals, $totalInvestment) {
                return $registeredReferrals >= $candidate->minimum_referrals
                    && $totalInvestment >= (float) $candidate->minimum_investment;
            }) ?? UserLevel::query()->orderBy('rank')->firstOrFail();
    }

    public static function profilePowerSummaryForMetrics(array $metrics): array
    {
        $level = self::resolveUserLevelForMetrics(
            (int) ($metrics['registered_referrals'] ?? 0),
            (float) ($metrics['total_invested'] ?? 0)
        );

        $verifiedInvites = max((int) ($metrics['verified_invites'] ?? 0), 0);
        $registeredReferrals = max((int) ($metrics['registered_referrals'] ?? 0), 0);
        $activeDirectInvestors = max((int) ($metrics['active_direct_investors'] ?? 0), 0);
        $totalInvested = max((float) ($metrics['total_invested'] ?? 0), 0);

        $components = [
            [
                'label' => 'Level strength',
                'value' => min(((int) $level->rank) * 18, 30),
                'display' => $level->name,
            ],
            [
                'label' => 'Verified invites',
                'value' => min($verifiedInvites * 2, 20),
                'display' => (string) $verifiedInvites,
            ],
            [
                'label' => 'Registered referrals',
                'value' => min($registeredReferrals * 2, 20),
                'display' => (string) $registeredReferrals,
            ],
            [
                'label' => 'Active team investors',
                'value' => min($activeDirectInvestors * 8, 20),
                'display' => (string) $activeDirectInvestors,
            ],
            [
                'label' => 'Investment commitment',
                'value' => min((int) floor($totalInvested / 250), 10),
                'display' => '$'.number_format($totalInvested, 0),
            ],
        ];

        $score = min((int) round(collect($components)->sum('value')), 100);

        $ranks = [
            ['min' => 0, 'max' => 24, 'label' => 'Starter Signal', 'accent' => 'secondary'],
            ['min' => 25, 'max' => 44, 'label' => 'Builder Rank', 'accent' => 'info'],
            ['min' => 45, 'max' => 64, 'label' => 'Connector Rank', 'accent' => 'primary'],
            ['min' => 65, 'max' => 84, 'label' => 'Influencer Rank', 'accent' => 'warning'],
            ['min' => 85, 'max' => 100, 'label' => 'Powerhouse Rank', 'accent' => 'success'],
        ];

        $currentRank = collect($ranks)->first(fn (array $rank) => $score >= $rank['min'] && $score <= $rank['max']) ?? $ranks[0];

        return [
            'score' => $score,
            'rank_label' => $currentRank['label'],
            'rank_accent' => $currentRank['accent'],
            'level' => $level,
            'components' => $components,
        ];
    }

    public static function mockManagerScenario(Miner $miner, InvestmentPackage $package, array $inputs): array
    {
        self::ensureDefaults();

        $verifiedInvites = max((int) ($inputs['verified_invites'] ?? 0), 0);
        $registeredReferrals = max((int) ($inputs['registered_referrals'] ?? 0), 0);
        $monthlyHashrate = max((float) ($inputs['monthly_hashrate_th'] ?? 0), 0);
        $monthlyRevenue = max((float) ($inputs['monthly_revenue_usd'] ?? 0), 0);
        $electricityCost = max((float) ($inputs['monthly_electricity_cost_usd'] ?? 0), 0);
        $maintenanceCost = max((float) ($inputs['monthly_maintenance_cost_usd'] ?? 0), 0);
        $activeShares = max((int) ($inputs['active_shares'] ?? 0), 0);

        $networkInputs = [
            'level_1_basic_subscribers' => max((int) ($inputs['level_1_basic_subscribers'] ?? 0), 0),
            'level_1_growth_subscribers' => max((int) ($inputs['level_1_growth_subscribers'] ?? 0), 0),
            'level_1_scale_subscribers' => max((int) ($inputs['level_1_scale_subscribers'] ?? 0), 0),
            'level_2_basic_subscribers' => max((int) ($inputs['level_2_basic_subscribers'] ?? 0), 0),
            'level_2_growth_subscribers' => max((int) ($inputs['level_2_growth_subscribers'] ?? 0), 0),
            'level_2_scale_subscribers' => max((int) ($inputs['level_2_scale_subscribers'] ?? 0), 0),
            'level_3_basic_subscribers' => max((int) ($inputs['level_3_basic_subscribers'] ?? 0), 0),
            'level_3_growth_subscribers' => max((int) ($inputs['level_3_growth_subscribers'] ?? 0), 0),
            'level_3_scale_subscribers' => max((int) ($inputs['level_3_scale_subscribers'] ?? 0), 0),
            'level_4_basic_subscribers' => max((int) ($inputs['level_4_basic_subscribers'] ?? 0), 0),
            'level_4_growth_subscribers' => max((int) ($inputs['level_4_growth_subscribers'] ?? 0), 0),
            'level_4_scale_subscribers' => max((int) ($inputs['level_4_scale_subscribers'] ?? 0), 0),
            'level_5_basic_subscribers' => max((int) ($inputs['level_5_basic_subscribers'] ?? 0), 0),
            'level_5_growth_subscribers' => max((int) ($inputs['level_5_growth_subscribers'] ?? 0), 0),
            'level_5_scale_subscribers' => max((int) ($inputs['level_5_scale_subscribers'] ?? 0), 0),
        ];

        $activeDirectInvestors = $networkInputs['level_1_basic_subscribers']
            + $networkInputs['level_1_growth_subscribers']
            + $networkInputs['level_1_scale_subscribers'];

        $profilePower = self::profilePowerSummaryForMetrics([
            'verified_invites' => $verifiedInvites,
            'registered_referrals' => $registeredReferrals,
            'active_direct_investors' => $activeDirectInvestors,
            'total_invested' => (float) $package->price,
        ]);

        $level = $profilePower['level'];
        $invitationBonusRate = self::invitationBonusRateForCount($verifiedInvites);
        $teamInvestorBonusRate = self::teamInvestorBonusRateForCount($activeDirectInvestors);
        $teamBonusRate = self::teamBonusRateForCounts($verifiedInvites, $activeDirectInvestors);
        $profilePowerRewardRate = self::investmentProfilePowerRewardRateForScore((float) $package->price, (float) $profilePower['score']);
        $baseRate = (float) $package->monthly_return_rate;
        $totalRewardRate = round($baseRate + (float) $level->bonus_rate + $teamBonusRate + $profilePowerRewardRate, 4);
        $projectedPackageProfit = round((float) $package->price * $totalRewardRate, 2);

        $monthlyNetProfit = round(max($monthlyRevenue - $electricityCost - $maintenanceCost, 0), 2);
        $monthlyRevenuePerShare = $activeShares > 0 ? round($monthlyNetProfit / $activeShares, 4) : 0.0;
        $personalMinerIncome = round($monthlyRevenuePerShare * (float) $package->shares_count, 2);

        $tierAmounts = [
            'basic' => 100.0,
            'growth' => 500.0,
            'scale' => 1000.0,
        ];

        $levelVolumes = collect(range(1, 5))
            ->mapWithKeys(function (int $depth) use ($networkInputs, $tierAmounts) {
                return [
                    $depth => round(
                        ($networkInputs['level_'.$depth.'_basic_subscribers'] * $tierAmounts['basic'])
                        + ($networkInputs['level_'.$depth.'_growth_subscribers'] * $tierAmounts['growth'])
                        + ($networkInputs['level_'.$depth.'_scale_subscribers'] * $tierAmounts['scale']),
                        2
                    ),
                ];
            });

        $referralRegistrationReward = round($registeredReferrals * (float) self::rewardSetting('referral_registration_reward'), 2);
        $directSubscriptionReward = round((float) $levelVolumes->get(1, 0) * (float) self::rewardSetting('referral_subscription_reward_rate'), 2);
        $teamRewardsByLevel = collect(range(1, 5))
            ->mapWithKeys(fn (int $depth) => [
                $depth => round((float) $levelVolumes->get($depth, 0) * self::networkLevelRewardRate($depth), 2),
            ]);
        $networkRewardTotal = round(
            $referralRegistrationReward + $directSubscriptionReward + (float) $teamRewardsByLevel->sum(),
            2
        );
        $rewardEngineProfit = round($projectedPackageProfit + $networkRewardTotal, 2);
        $finalProjectedProfit = round($rewardEngineProfit + $personalMinerIncome, 2);

        $capRate = self::investmentProfilePowerRewardCap((float) $package->price);
        $remainingPower = max(100 - (int) $profilePower['score'], 0);

        return [
            'package' => $package,
            'miner' => $miner,
            'profile_power' => $profilePower,
            'reward_rates' => [
                'base_rate' => $baseRate,
                'level_bonus_rate' => (float) $level->bonus_rate,
                'invitation_bonus_rate' => $invitationBonusRate,
                'team_investor_bonus_rate' => $teamInvestorBonusRate,
                'team_bonus_rate' => $teamBonusRate,
                'profile_power_reward_rate' => $profilePowerRewardRate,
                'cap_rate' => $capRate,
                'total_rate' => $totalRewardRate,
            ],
            'network' => [
                'active_direct_investors' => $activeDirectInvestors,
                'volumes' => $levelVolumes->all(),
                'inputs' => $networkInputs,
            ],
            'miner_metrics' => [
                'monthly_hashrate_th' => $monthlyHashrate,
                'monthly_revenue_usd' => $monthlyRevenue,
                'monthly_electricity_cost_usd' => $electricityCost,
                'monthly_maintenance_cost_usd' => $maintenanceCost,
                'monthly_net_profit_usd' => $monthlyNetProfit,
                'active_shares' => $activeShares,
                'monthly_revenue_per_share_usd' => $monthlyRevenuePerShare,
                'personal_miner_income_usd' => $personalMinerIncome,
                'efficiency_per_th_usd' => $monthlyHashrate > 0 ? round($monthlyRevenue / $monthlyHashrate, 2) : 0.0,
            ],
            'profits' => [
                'projected_package_profit' => $projectedPackageProfit,
                'referral_registration_reward' => $referralRegistrationReward,
                'direct_subscription_reward' => $directSubscriptionReward,
                'team_rewards_by_level' => $teamRewardsByLevel->all(),
                'network_reward_total' => $networkRewardTotal,
                'reward_engine_profit' => $rewardEngineProfit,
                'final_projected_profit' => $finalProjectedProfit,
            ],
            'guidance' => [
                'remaining_power_points' => $remainingPower,
                'full_cap_unlocked' => (int) $profilePower['score'] >= 100,
            ],
        ];
    }

    public static function investmentTotalRewardRate(UserInvestment $investment): float
    {
        return round(
            (float) $investment->monthly_return_rate
            + (float) $investment->level_bonus_rate
            + (float) $investment->team_bonus_rate
            + self::investmentProfilePowerRewardRate($investment),
            4
        );
    }

    public static function investmentProjectedRewardAmount(UserInvestment $investment): float
    {
        return round((float) $investment->amount * self::investmentTotalRewardRate($investment), 2);
    }

    public static function investmentProjectedRewardAmountForScore(UserInvestment $investment, float $powerScore): float
    {
        return round(
            (float) $investment->amount * (
                (float) $investment->monthly_return_rate
                + (float) $investment->level_bonus_rate
                + (float) $investment->team_bonus_rate
                + self::investmentProfilePowerRewardRateForScore((float) $investment->amount, $powerScore)
            ),
            2
        );
    }

    public static function unlockedRewardCapBadges(User $user): array
    {
        $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);

        $score = (int) (self::profilePowerSummary($user)['score'] ?? 0);
        $activeInvestments = $user->investments->where('status', 'active');

        $tiers = [
            'basic' => [
                'label' => 'Basic 100',
                'short' => '4% cap',
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500,
            ],
            'growth' => [
                'label' => 'Growth 500',
                'short' => '6% cap',
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000,
            ],
            'scale' => [
                'label' => 'Scale 1000+',
                'short' => '7% cap',
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount >= 1000,
            ],
        ];

        return collect($tiers)
            ->map(function (array $tier, string $key) use ($score, $activeInvestments) {
                return [
                    'key' => $key,
                    'label' => $tier['label'],
                    'short' => $tier['short'],
                    'unlocked' => $score >= 100 && $activeInvestments->contains($tier['matches']),
                ];
            })
            ->filter(fn (array $tier) => $tier['unlocked'])
            ->values()
            ->all();
    }

    public static function ensureDefaults(): void
    {
        if (! app()->runningUnitTests() && self::$defaultsEnsuredForRequest) {
            return;
        }

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
        self::$defaultsEnsuredForRequest = true;
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

        return self::invitationBonusRateForCount($verifiedInvites);
    }

    public static function teamBonusRate(User $user): float
    {
        $activeDirectInvestors = self::activeDirectInvestorCount($user);

        return self::teamBonusRateForCounts(
            $user->friendInvitations()->whereNotNull('verified_at')->count(),
            $activeDirectInvestors
        );
    }

    public static function activeDirectInvestorCount(User $user): int
    {
        if ($user->relationLoaded('sponsoredUsers')) {
            return $user->sponsoredUsers
                ->filter(fn (User $sponsoredUser) => $sponsoredUser->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty())
                ->count();
        }

        return $user->sponsoredUsers()
            ->whereHas('investments', fn ($query) => $query->where('status', 'active')->where('amount', '>', 0))
            ->count();
    }

    public static function referralTree(Collection $users, int $maxDepth = 5): Collection
    {
        $usersBySponsor = $users
            ->sortBy(fn (User $user) => Str::lower($user->name))
            ->groupBy(fn (User $user) => $user->sponsor_user_id ?? 'root');

        return self::mapReferralTreeNodes($usersBySponsor, 'root', 1, $maxDepth)->values();
    }

    public static function referralSubtree(Collection $users, User $focusUser, int $maxDepth = 5): Collection
    {
        $usersBySponsor = $users
            ->sortBy(fn (User $user) => Str::lower($user->name))
            ->groupBy(fn (User $user) => $user->sponsor_user_id ?? 'root');

        $rootNode = self::mapReferralTreeNode($usersBySponsor, $focusUser, 1, $maxDepth);
        $rootNode['situation'] = self::treeNodeSituation($rootNode);

        return collect([
            $rootNode,
        ])->values();
    }

    public static function referralTreeSummary(Collection $tree): array
    {
        $flattened = self::flattenReferralTree($tree);

        return [
            'root_count' => $tree->count(),
            'visible_nodes' => $flattened->count(),
            'max_depth' => (int) $flattened->max('depth'),
            'leaf_nodes' => $flattened->where('children_count', 0)->count(),
        ];
    }

    public static function flattenedReferralTree(Collection $tree): Collection
    {
        return self::flattenReferralTree($tree);
    }

    public static function compactTreePowerSummary(User $user): array
    {
        $verifiedInvites = $user->relationLoaded('friendInvitations')
            ? $user->friendInvitations->whereNotNull('verified_at')->count()
            : 0;

        $activeInvestments = $user->relationLoaded('investments')
            ? $user->investments->where('status', 'active')->where('amount', '>', 0)
            : collect();

        $activeCapital = (float) $activeInvestments->sum('amount');
        $activeDirectInvestors = self::activeDirectInvestorCount($user);
        $levelRank = (int) ($user->userLevel?->rank ?? 1);

        $score = min(
            ($levelRank * 18)
            + min($verifiedInvites * 2, 18)
            + min($activeDirectInvestors * 10, 30)
            + min((int) floor($activeCapital / 200), 34),
            100
        );

        $rank = match (true) {
            $score >= 85 => 'Powerhouse',
            $score >= 65 => 'Influencer',
            $score >= 45 => 'Connector',
            $score >= 25 => 'Builder',
            default => 'Starter',
        };

        return [
            'score' => (int) $score,
            'rank' => $rank,
        ];
    }

    public static function referralTreeChartPayload(Collection $tree, string $rootLabel = 'Network'): array
    {
        $links = [];
        $nodes = [[
            'id' => 'network-root',
            'title' => $rootLabel,
            'name' => 'Sponsor Tree',
            'color' => '#f59e0b',
            'situation' => 'Platform root',
            'priority' => 'Overview',
            'level_name' => 'Root',
            'sponsor_name' => 'Top-level',
            'power' => '—',
            'direct_team' => (string) $tree->count(),
            'active_direct' => (string) collect(self::flattenReferralTree($tree))->sum('active_direct_investors'),
            'capital' => '$'.number_format((float) collect(self::flattenReferralTree($tree))->sum('active_capital'), 2),
            'verified_invites' => (string) collect(self::flattenReferralTree($tree))->sum('verified_invites'),
            'profile_url' => '',
        ]];

        foreach ($tree as $node) {
            self::appendReferralTreeChartNode($node, 'network-root', $nodes, $links);
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }

    public static function treeNodeSituation(array $node): array
    {
        $situation = match (true) {
            $node['active_direct_investors'] >= 3 && $node['active_capital'] >= 1000 => [
                'label' => 'Strong branch',
                'description' => 'This investor already drives capital and active referrals, so this branch is contributing meaningful momentum.',
                'health' => 'Healthy branch',
                'action_hint' => 'Keep this branch engaged and monitor expansion into the next visible level.',
            ],
            $node['direct_team'] >= 2 && $node['active_direct_investors'] === 0 => [
                'label' => 'Conversion gap',
                'description' => 'This branch is bringing people in, but they have not converted into active investors yet.',
                'health' => 'Invite-heavy, low conversion',
                'action_hint' => 'Focus on converting direct referrals into their first active investment.',
            ],
            $node['active_capital'] > 0 && $node['direct_team'] === 0 => [
                'label' => 'Investor only',
                'description' => 'This user has active capital in the miner, but has not started building a visible referral branch yet.',
                'health' => 'Capital without depth',
                'action_hint' => 'Encourage this investor to activate referrals and begin building a branch.',
            ],
            $node['direct_team'] > 0 => [
                'label' => 'Growing branch',
                'description' => 'This branch is building structure and has room to convert more direct members into active investors.',
                'health' => 'Needs activation',
                'action_hint' => 'Support this branch with follow-up and activation messaging before it stalls.',
            ],
            default => [
                'label' => 'Early stage',
                'description' => 'This node is still at an early stage, with limited branch activity and low current capital impact.',
                'health' => 'Early development',
                'action_hint' => 'This branch still needs invites, verified users, and its first active investor.',
            ],
        };

        $priority = match (true) {
            $node['active_direct_investors'] >= 3 || $node['active_capital'] >= 1000 => 'High value',
            $node['direct_team'] >= 2 => 'Watch closely',
            default => 'Develop',
        };

        return [
            'label' => $situation['label'],
            'description' => $situation['description'],
            'health' => $situation['health'],
            'action_hint' => $situation['action_hint'],
            'priority' => $priority,
        ];
    }

    public static function profilePowerSummary(User $user): array
    {
        $level = $user->relationLoaded('userLevel') && $user->userLevel
            ? $user->userLevel
            : self::syncUserLevel($user);
        $starterProgress = self::starterUpgradeProgress($user);

        $verifiedInvites = $user->relationLoaded('friendInvitations')
            ? $user->friendInvitations->whereNotNull('verified_at')->count()
            : $user->friendInvitations()->whereNotNull('verified_at')->count();

        $registeredReferrals = $user->relationLoaded('friendInvitations')
            ? $user->friendInvitations->whereNotNull('registered_at')->count()
            : $user->friendInvitations()->whereNotNull('registered_at')->count();

        $activeInvestments = $user->relationLoaded('investments')
            ? $user->investments->where('status', 'active')
            : $user->investments()->where('status', 'active')->get();

        $activePackageCount = $activeInvestments->count();
        $totalInvested = (float) $activeInvestments->sum('amount');
        $activeDirectInvestors = self::activeDirectInvestorCount($user);

        $components = [
            [
                'label' => 'Level strength',
                'value' => min(((int) $level->rank) * 18, 30),
                'display' => $level->name,
                'description' => 'Your current investor level adds stable account weight.',
            ],
            [
                'label' => 'Verified invites',
                'value' => min($verifiedInvites * 2, 20),
                'display' => (string) $verifiedInvites,
                'description' => 'Verified contacts prove real network reach.',
            ],
            [
                'label' => 'Registered referrals',
                'value' => min($registeredReferrals * 2, 20),
                'display' => (string) $registeredReferrals,
                'description' => 'Registered team members increase your conversion power.',
            ],
            [
                'label' => 'Active team investors',
                'value' => min($activeDirectInvestors * 8, 20),
                'display' => (string) $activeDirectInvestors,
                'description' => 'Direct investors make your profile stronger fastest.',
            ],
            [
                'label' => 'Investment commitment',
                'value' => min((int) floor($totalInvested / 250), 10),
                'display' => '$'.number_format($totalInvested, 0),
                'description' => 'Capital commitment supports rank credibility.',
            ],
        ];

        $score = min((int) round(collect($components)->sum('value')), 100);

        $ranks = [
            ['min' => 0, 'max' => 24, 'label' => 'Starter Signal', 'accent' => 'secondary', 'icon' => 'sparkles'],
            ['min' => 25, 'max' => 44, 'label' => 'Builder Rank', 'accent' => 'info', 'icon' => 'hammer'],
            ['min' => 45, 'max' => 64, 'label' => 'Connector Rank', 'accent' => 'primary', 'icon' => 'network'],
            ['min' => 65, 'max' => 84, 'label' => 'Influencer Rank', 'accent' => 'warning', 'icon' => 'badge-plus'],
            ['min' => 85, 'max' => 100, 'label' => 'Powerhouse Rank', 'accent' => 'success', 'icon' => 'crown'],
        ];

        $currentRank = collect($ranks)->first(fn (array $rank) => $score >= $rank['min'] && $score <= $rank['max']) ?? $ranks[0];
        $nextRank = collect($ranks)->first(fn (array $rank) => $rank['min'] > $score);
        $currentMin = (int) $currentRank['min'];
        $nextThreshold = $nextRank['min'] ?? 100;
        $progressDenominator = max($nextThreshold - $currentMin, 1);
        $progressWithinRank = min(max((($score - $currentMin) / $progressDenominator) * 100, 0), 100);

        $achievements = collect([
            [
                'title' => 'Verified circle',
                'icon' => 'badge-check',
                'description' => 'Reach 10 verified invites.',
                'unlocked' => $verifiedInvites >= 10,
            ],
            [
                'title' => 'Conversion builder',
                'icon' => 'user-round-check',
                'description' => 'Convert 3 referrals into registered members.',
                'unlocked' => $registeredReferrals >= 3,
            ],
            [
                'title' => 'Investor magnet',
                'icon' => 'gem',
                'description' => 'Bring in your first active direct investor.',
                'unlocked' => $activeDirectInvestors >= 1,
            ],
            [
                'title' => 'Capital anchor',
                'icon' => 'wallet-cards',
                'description' => 'Hold at least $1,000 in active mining capital.',
                'unlocked' => $totalInvested >= 1000,
            ],
        ])->values()->all();

        $milestones = collect([
            [
                'title' => 'Starter mission',
                'status' => $starterProgress['has_unlocked_basic'] ? 'completed' : ($starterProgress['qualifies'] ? 'ready' : 'in_progress'),
                'description' => 'Complete the free upgrade mission for Basic 100 access.',
                'current' => $starterProgress['verified_invites'].'/'.$starterProgress['required_verified_invites'].' verified and '.$starterProgress['direct_basic_subscribers'].'/'.$starterProgress['required_direct_basic_subscribers'].' direct basic investors',
            ],
            [
                'title' => 'Network bonus unlocked',
                'status' => $activeDirectInvestors >= 1 ? 'completed' : 'locked',
                'description' => 'Your first direct investor activates team-bonus growth.',
                'current' => $activeDirectInvestors.' / 1 active direct investors',
            ],
            [
                'title' => 'Connector threshold',
                'status' => $score >= 45 ? 'completed' : 'locked',
                'description' => 'Cross 45 profile power to establish visible network strength.',
                'current' => $score.' / 45 profile power',
            ],
            [
                'title' => 'Powerhouse threshold',
                'status' => $score >= 85 ? 'completed' : 'locked',
                'description' => 'Reach elite profile power with strong capital and team conversion.',
                'current' => $score.' / 85 profile power',
            ],
        ])->values()->all();

        $recommendedActions = collect();

        if (! $starterProgress['has_unlocked_basic']) {
            $recommendedActions->push([
                'title' => 'Complete your starter mission',
                'description' => 'Finish the free upgrade mission to unlock Basic 100 and a visible profile jump.',
                'target' => $starterProgress['verified_invites'].'/'.$starterProgress['required_verified_invites'].' verified and '.$starterProgress['direct_basic_subscribers'].'/'.$starterProgress['required_direct_basic_subscribers'].' direct basic investors',
                'route' => route('dashboard.friends'),
                'route_label' => 'Grow starter mission',
                'icon' => 'rocket',
            ]);
        }

        if ($registeredReferrals < 3) {
            $recommendedActions->push([
                'title' => 'Turn invites into registrations',
                'description' => 'Focus on getting invited contacts to complete registration so your rank grows faster.',
                'target' => '3 registered referrals',
                'route' => route('dashboard.network'),
                'route_label' => 'Open network',
                'icon' => 'users',
            ]);
        }

        if ($activeDirectInvestors < 1) {
            $recommendedActions->push([
                'title' => 'Get your first active investor',
                'description' => 'One direct investor is the fastest way to unlock visible power and team bonus growth.',
                'target' => '1 active direct investor',
                'route' => route('dashboard.buy-shares'),
                'route_label' => 'Review packages',
                'icon' => 'gem',
            ]);
        }

        if ($totalInvested < 1000) {
            $recommendedActions->push([
                'title' => 'Strengthen capital commitment',
                'description' => 'Larger active capital improves both profile credibility and long-term earnings momentum.',
                'target' => '$1,000 active capital',
                'route' => route('dashboard.buy-shares'),
                'route_label' => 'Buy shares',
                'icon' => 'wallet',
            ]);
        }

        if ($recommendedActions->isEmpty()) {
            $recommendedActions->push([
                'title' => 'Maintain your growth pace',
                'description' => 'Keep scaling both capital and direct investor quality to hold your current profile advantage.',
                'target' => 'Stay above your current rank',
                'route' => route('dashboard.network'),
                'route_label' => 'Review team',
                'icon' => 'shield-check',
            ]);
        }

        $rankPerks = match ($currentRank['label']) {
            'Starter Signal' => [
                'Visible starter badge on your profile',
                'Entry-level power tracking unlocked',
            ],
            'Builder Rank' => [
                'Builder badge styling across network views',
                'More visible credibility in investor pipeline rows',
            ],
            'Connector Rank' => [
                'Connector badge styling and stronger branch presence',
                'Milestone visibility becomes more persuasive to downline viewers',
            ],
            'Influencer Rank' => [
                'Influencer badge styling across profile and network surfaces',
                'Higher social proof for referrals and investor conversion',
            ],
            default => [
                'Elite Powerhouse badge styling across the platform',
                'Top-tier social proof inside investor and team views',
            ],
        };

        return [
            'score' => $score,
            'rank_label' => $currentRank['label'],
            'rank_accent' => $currentRank['accent'],
            'rank_icon' => $currentRank['icon'],
            'next_rank_label' => $nextRank['label'] ?? 'Maximum rank reached',
            'next_rank_threshold' => $nextThreshold,
            'points_to_next_rank' => max($nextThreshold - $score, 0),
            'progress_within_rank' => $progressWithinRank,
            'verified_invites' => $verifiedInvites,
            'registered_referrals' => $registeredReferrals,
            'active_direct_investors' => $activeDirectInvestors,
            'active_package_count' => $activePackageCount,
            'total_invested' => $totalInvested,
            'components' => $components,
            'achievements' => $achievements,
            'milestones' => $milestones,
            'recommended_actions' => $recommendedActions->values()->all(),
            'rank_perks' => $rankPerks,
            'starter_progress' => $starterProgress,
        ];
    }

    public static function profilePowerLeaderboard(int $limit = 5): Collection
    {
        $users = User::query()
            ->whereNotNull('email_verified_at')
            ->with([
                'userLevel',
                'friendInvitations',
                'sponsoredUsers.investments',
                'investments',
            ])
            ->get();

        return $users
            ->map(function (User $user) {
                return [
                    'user' => $user,
                    'summary' => self::profilePowerSummary($user),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['summary']['score'])
            ->values()
            ->take($limit);
    }

    public static function miningEarningsStreak(User $user): int
    {
        $earningDates = $user->earnings()
            ->where('source', 'mining_daily_share')
            ->orderByDesc('earned_on')
            ->pluck('earned_on')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($earningDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = Carbon::parse($earningDates->first());

        foreach ($earningDates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }

            $streak++;
            $cursor = $cursor->copy()->subDay();
        }

        return $streak;
    }

    public static function weeklyMomentumSummary(User $user): array
    {
        return self::momentumSummaryForWindow($user, now()->subDays(6)->startOfDay(), 'Last 7 days', 7);
    }

    public static function momentumSummaryForWindow(User $user, Carbon $start, string $label, int $days): array
    {
        $end = $start->copy()->addDays(max($days - 1, 0))->endOfDay();

        $verifiedInvites = $user->friendInvitations()
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', $start)
            ->where('verified_at', '<=', $end)
            ->count();

        $registeredReferrals = $user->friendInvitations()
            ->whereNotNull('registered_at')
            ->where('registered_at', '>=', $start)
            ->where('registered_at', '<=', $end)
            ->count();

        $newActiveDirectInvestors = $user->sponsoredUsers()
            ->whereHas('investments', fn ($query) => $query->where('status', 'active')->where('amount', '>', 0)->whereBetween('subscribed_at', [$start, $end]))
            ->count();

        $miningIncome = (float) $user->earnings()
            ->where('source', 'mining_daily_share')
            ->whereBetween('earned_on', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $streak = $days === 7 ? self::miningEarningsStreak($user) : 0;
        $score = min(
            ($verifiedInvites * 10)
            + ($registeredReferrals * 12)
            + ($newActiveDirectInvestors * 20)
            + min($streak * 4, 20)
            + min((int) floor($miningIncome / 5), 20),
            100
        );

        return [
            'score' => $score,
            'verified_invites' => $verifiedInvites,
            'registered_referrals' => $registeredReferrals,
            'new_active_direct_investors' => $newActiveDirectInvestors,
            'mining_income' => round($miningIncome, 2),
            'streak_days' => $streak,
            'window_label' => $label,
        ];
    }

    public static function weeklyMomentumHistory(User $user, int $weeks = 4): Collection
    {
        return collect(range(0, $weeks - 1))
            ->map(function (int $offset) use ($user) {
                $start = now()->startOfWeek()->subWeeks($offset);
                $summary = self::momentumSummaryForWindow($user, $start, 'Week of '.$start->format('M d'), 7);
                $summary['week_label'] = $start->format('M d');

                return $summary;
            })
            ->values();
    }

    public static function monthlyMomentumSummary(User $user): array
    {
        $start = now()->startOfMonth();

        return self::momentumSummaryForWindow($user, $start, $start->format('F').' momentum', $start->daysInMonth);
    }

    public static function competitionLeaderboard(string $category, int $limit = 10): Collection
    {
        $users = User::query()
            ->whereNotNull('email_verified_at')
            ->with([
                'userLevel',
                'friendInvitations',
                'sponsoredUsers.investments',
                'investments',
            ])
            ->get()
            ->map(function (User $user) {
                return [
                    'user' => $user,
                    'profile_power' => self::profilePowerSummary($user),
                    'weekly_momentum' => self::weeklyMomentumSummary($user),
                    'monthly_momentum' => self::monthlyMomentumSummary($user),
                ];
            });

        $sorted = match ($category) {
            'weekly' => $users->sortByDesc(fn (array $row) => $row['weekly_momentum']['score']),
            'monthly' => $users->sortByDesc(fn (array $row) => $row['monthly_momentum']['score']),
            default => $users->sortByDesc(fn (array $row) => $row['profile_power']['score']),
        };

        return $sorted->values()->take($limit);
    }

    public static function captureCompetitionSnapshot(string $category, int $limit = 10): Collection
    {
        [$periodStart, $periodEnd] = match ($category) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            default => throw new RuntimeException('Unsupported competition snapshot category.'),
        };

        $leaders = self::competitionLeaderboard($category, $limit);

        foreach ($leaders as $index => $leader) {
            $metric = $category === 'weekly' ? $leader['weekly_momentum'] : $leader['monthly_momentum'];

            $snapshot = HallOfFameSnapshot::query()
                ->where('category', $category)
                ->where('user_id', $leader['user']->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->first() ?? new HallOfFameSnapshot([
                    'category' => $category,
                    'period_start' => $periodStart->toDateString(),
                    'user_id' => $leader['user']->id,
                ]);

            $snapshot->fill([
                'period_end' => $periodEnd->toDateString(),
                'rank_position' => $index + 1,
                'score' => $metric['score'],
                'profile_power_score' => $leader['profile_power']['score'],
                'rank_label' => $leader['profile_power']['rank_label'],
                'highlights' => [
                    'verified_invites' => $metric['verified_invites'],
                    'registered_referrals' => $metric['registered_referrals'],
                    'new_active_direct_investors' => $metric['new_active_direct_investors'],
                    'mining_income' => $metric['mining_income'],
                ],
            ])->save();

            if ($index === 0) {
                self::maybeCelebrateHallOfFameWinner(
                    $leader['user'],
                    $category,
                    $periodStart,
                    $periodEnd,
                    $metric['score'],
                    $leader['profile_power']
                );
            }
        }

        return $leaders;
    }

    public static function hallOfFameWinnerHistory(string $category, int $periods = 6): Collection
    {
        return HallOfFameSnapshot::query()
            ->with('user')
            ->where('category', $category)
            ->where('rank_position', 1)
            ->latest('period_start')
            ->take($periods)
            ->get()
            ->map(function (HallOfFameSnapshot $snapshot) {
                return [
                    'user' => $snapshot->user,
                    'period_start' => $snapshot->period_start,
                    'period_end' => $snapshot->period_end,
                    'score' => $snapshot->score,
                    'profile_power_score' => $snapshot->profile_power_score,
                    'rank_label' => $snapshot->rank_label,
                    'highlights' => $snapshot->highlights ?? [],
                ];
            })
            ->values();
    }

    public static function maybeCelebrateHallOfFameWinner(
        User $user,
        string $category,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $score,
        array $profilePower
    ): void {
        $eventKey = 'hall_of_fame_'.$category.'_winner';

        $alreadyCelebrated = $user->notifications()
            ->where('type', ActivityFeedNotification::class)
            ->get()
            ->contains(function ($notification) use ($eventKey, $periodStart) {
                return ($notification->data['event_key'] ?? null) === $eventKey
                    && ($notification->data['period_start'] ?? null) === $periodStart->toDateString();
            });

        if ($alreadyCelebrated) {
            return;
        }

        $label = $category === 'weekly' ? 'Weekly Hall of Fame winner' : 'Monthly Hall of Fame champion';
        $notes = $category === 'weekly'
            ? 'You led the weekly momentum board and secured the top spot in the Hall of Fame.'
            : 'You led the monthly branch-building board and secured the top champion spot in the Hall of Fame.';

        $user->notify(new ActivityFeedNotification([
            'event_key' => $eventKey,
            'category' => 'milestone',
            'status' => 'success',
            'subject' => $label,
            'message' => 'You reached #1 in the '.$category.' Hall of Fame with '.$score.' points.',
            'context_label' => 'Period',
            'context_value' => $periodStart->format('M d').' - '.$periodEnd->format('M d'),
            'status_line' => 'Rank secured: '.$profilePower['rank_label'],
            'notes_line' => $notes,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'power_score' => $profilePower['score'],
            'rank_label' => $profilePower['rank_label'],
            'rank_icon' => $profilePower['rank_icon'],
            'force_mail' => false,
        ]));
    }

    public static function maybeCelebrateProfilePower(User $user): void
    {
        $user->loadMissing([
            'userLevel',
            'friendInvitations',
            'sponsoredUsers.investments',
            'investments.package',
        ]);

        $summary = self::profilePowerSummary($user);

        if ($summary['score'] >= 25) {
            $alreadyCelebrated = $user->notifications()
                ->where('type', ActivityFeedNotification::class)
                ->get()
                ->contains(function ($notification) use ($summary) {
                    return ($notification->data['event_key'] ?? null) === 'profile_power_rank'
                        && ($notification->data['rank_label'] ?? null) === $summary['rank_label'];
                });

            if (! $alreadyCelebrated) {
                $user->notify(new ActivityFeedNotification([
                    'event_key' => 'profile_power_rank',
                    'category' => 'milestone',
                    'status' => 'success',
                    'subject' => 'New profile rank unlocked',
                    'message' => 'You reached '.$summary['rank_label'].' with '.$summary['score'].' profile power.',
                    'context_label' => 'Current power',
                    'context_value' => $summary['score'].' / 100',
                    'status_line' => 'Rank unlocked: '.$summary['rank_label'],
                    'notes_line' => 'Keep growing verified invites, direct investors, and active capital to reach '.$summary['next_rank_label'].'.',
                    'rank_label' => $summary['rank_label'],
                    'rank_icon' => $summary['rank_icon'],
                    'power_score' => $summary['score'],
                    'force_mail' => false,
                ]));
            }
        }

        self::maybeCelebrateProfilePowerRewardCaps($user, $summary);
    }

    public static function maybeCelebrateProfilePowerRewardCaps(User $user, ?array $summary = null): void
    {
        $resolvedSummary = $summary ?? self::profilePowerSummary($user);

        if (($resolvedSummary['score'] ?? 0) < 100) {
            return;
        }

        $user->loadMissing(['investments.package']);

        $activeInvestments = $user->investments->where('status', 'active')->where('amount', '>', 0);

        if ($activeInvestments->isEmpty()) {
            return;
        }

        $existingCelebrations = $user->notifications()
            ->where('type', ActivityFeedNotification::class)
            ->get();

        $tiers = [
            [
                'key' => 'basic',
                'label' => 'Basic 100',
                'max_rate' => (float) self::rewardSetting('profile_power_basic_max_rate'),
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500,
            ],
            [
                'key' => 'growth',
                'label' => 'Growth 500',
                'max_rate' => (float) self::rewardSetting('profile_power_growth_max_rate'),
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000,
            ],
            [
                'key' => 'scale',
                'label' => 'Scale 1000+',
                'max_rate' => (float) self::rewardSetting('profile_power_scale_max_rate'),
                'matches' => fn (UserInvestment $investment) => (float) $investment->amount >= 1000,
            ],
        ];

        foreach ($tiers as $tier) {
            if (! $activeInvestments->contains($tier['matches'])) {
                continue;
            }

            $alreadyCelebrated = $existingCelebrations->contains(function ($notification) use ($tier) {
                return ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap'
                    && ($notification->data['reward_cap_tier'] ?? null) === $tier['key'];
            });

            if ($alreadyCelebrated) {
                continue;
            }

            $user->notify(new ActivityFeedNotification([
                'event_key' => 'profile_power_reward_cap',
                'reward_cap_tier' => $tier['key'],
                'category' => 'milestone',
                'status' => 'success',
                'subject' => $tier['label'].' full reward cap unlocked',
                'message' => 'You unlocked the full '.number_format($tier['max_rate'] * 100, 2).'% profile power reward cap for '.$tier['label'].'.',
                'context_label' => 'Unlocked cap',
                'context_value' => number_format($tier['max_rate'] * 100, 2).'% monthly boost',
                'status_line' => 'Power score: '.($resolvedSummary['score'] ?? 100).' / 100',
                'notes_line' => 'This package tier now receives its full profile-power reward on top of the base return, level bonus, and team bonus.',
                'rank_label' => $resolvedSummary['rank_label'] ?? null,
                'rank_icon' => 'badge-percent',
                'power_score' => $resolvedSummary['score'] ?? 100,
                'force_mail' => false,
            ]));
        }
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
                'amount' => self::investmentProjectedRewardAmount($investment),
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
        self::maybeCelebrateProfilePower($user->fresh());

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
            self::maybeCelebrateProfilePower($refreshedUser->fresh());

            Earning::firstOrCreate(
                [
                    'user_id' => $refreshedUser->id,
                    'investment_id' => $investment->id,
                    'earned_on' => now()->toDateString(),
                    'source' => 'projected_return',
                ],
                [
                    'amount' => self::investmentProjectedRewardAmount($investment),
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
        self::maybeCelebrateProfilePower($invitation->user->fresh());

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

        $log = MinerPerformanceLog::query()
            ->where('miner_id', $miner->id)
            ->whereDate('logged_on', $loggedOn)
            ->first() ?? new MinerPerformanceLog([
                'miner_id' => $miner->id,
                'logged_on' => $loggedOn,
            ]);

        $log->fill([
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
        ]);

        $log->save();

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

                $earning = Earning::query()
                    ->where('user_id', $investment->user_id)
                    ->where('investment_id', $investment->id)
                    ->whereDate('earned_on', $log->logged_on->toDateString())
                    ->where('source', 'mining_daily_share')
                    ->first() ?? new Earning([
                        'user_id' => $investment->user_id,
                        'investment_id' => $investment->id,
                        'earned_on' => $log->logged_on->toDateString(),
                        'source' => 'mining_daily_share',
                    ]);

                $earning->fill([
                    'amount' => $amount,
                    'status' => 'available',
                    'notes' => 'Daily miner distribution from '.$log->miner->name.' on '.$log->logged_on->format('Y-m-d').' at $'.number_format((float) $log->revenue_per_share_usd, 4).' per share.',
                ]);

                $earning->save();

                return $earning;
                });
    }

    public static function minerPerformanceSummary(Miner $miner, int $days = 7): array
    {
        $logs = $miner->relationLoaded('performanceLogs')
            ? $miner->performanceLogs
                ->sortByDesc(fn (MinerPerformanceLog $log) => optional($log->logged_on)?->timestamp ?? 0)
                ->take($days)
                ->values()
            : $miner->performanceLogs()
                ->orderByDesc('logged_on')
                ->limit($days)
                ->get();

        $latestLog = $logs->first();
        $trendLogs = $logs
            ->sortBy(fn (MinerPerformanceLog $log) => optional($log->logged_on)?->timestamp ?? 0)
            ->values();

        $totalRevenue = round((float) $trendLogs->sum('revenue_usd'), 2);
        $totalCosts = round((float) $trendLogs->sum(function (MinerPerformanceLog $log) {
            return (float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd;
        }), 2);
        $totalNetProfit = round((float) $trendLogs->sum('net_profit_usd'), 2);

        return [
            'latest_log' => $latestLog,
            'logs' => $trendLogs,
            'total_revenue' => $totalRevenue,
            'total_costs' => $totalCosts,
            'total_net_profit' => $totalNetProfit,
            'average_hashrate' => round((float) $trendLogs->avg('hashrate_th'), 2),
            'average_uptime' => round((float) $trendLogs->avg('uptime_percentage'), 2),
            'average_revenue_per_share' => round((float) $trendLogs->avg('revenue_per_share_usd'), 4),
            'margin_rate' => $totalRevenue > 0 ? round(($totalNetProfit / $totalRevenue) * 100, 2) : 0.0,
        ];
    }

    public static function investmentLivePerformanceSummary(UserInvestment $investment, int $days = 7): array
    {
        $investment->loadMissing(['miner', 'package']);

        $startDate = now()->copy()->subDays(max($days - 1, 0))->startOfDay()->toDateString();
        $logs = $investment->miner
            ? $investment->miner->performanceLogs()
                ->whereDate('logged_on', '>=', $startDate)
                ->orderByDesc('logged_on')
                ->limit($days)
                ->get()
            : collect();

        $latestLog = $logs->first();
        $trendLogs = $logs
            ->sortBy(fn (MinerPerformanceLog $log) => optional($log->logged_on)?->timestamp ?? 0)
            ->values();

        $recentEarnings = $investment->earnings()
            ->where('source', 'mining_daily_share')
            ->whereDate('earned_on', '>=', $startDate)
            ->orderByDesc('earned_on')
            ->limit($days)
            ->get();

        $latestEarning = $recentEarnings->first();
        $sevenDayTotal = round((float) $recentEarnings->sum('amount'), 2);

        return [
            'investment' => $investment,
            'latest_log' => $latestLog,
            'latest_earning' => $latestEarning,
            'tracked_days' => (int) $recentEarnings->count(),
            'seven_day_total' => $sevenDayTotal,
            'average_daily_earning' => $recentEarnings->count() > 0 ? round($sevenDayTotal / $recentEarnings->count(), 2) : 0.0,
            'trend_labels' => $trendLogs->map(fn (MinerPerformanceLog $log) => $log->logged_on?->format('M d'))->values()->all(),
            'trend_values' => $trendLogs->map(fn (MinerPerformanceLog $log) => round((float) $log->revenue_per_share_usd * (float) $investment->shares_owned, 2))->values()->all(),
        ];
    }

    public static function expectedMonthlyEarnings(User $user): float
    {
        $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
        $powerScore = (float) (self::profilePowerSummary($user)['score'] ?? 0);

        $activeInvestments = $user->relationLoaded('investments')
            ? $user->investments->where('status', 'active')
            : $user->investments()->with('package')->where('status', 'active')->get();

        return (float) $activeInvestments->sum(function (UserInvestment $investment) use ($powerScore) {
            return self::investmentProjectedRewardAmountForScore($investment, $powerScore);
        });
    }

    public static function generateMonthlyEarnings(User $user, ?Carbon $month = null): Collection
    {
        $period = ($month ?? now())->copy()->startOfMonth();
        $user->loadMissing(['userLevel', 'friendInvitations', 'sponsoredUsers.investments']);
        $powerScore = (float) (self::profilePowerSummary($user)['score'] ?? 0);

        return $user->investments()
            ->with('package')
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->get()
            ->map(function (UserInvestment $investment) use ($user, $period, $powerScore) {
                $amount = self::investmentProjectedRewardAmountForScore($investment, $powerScore);

                return Earning::firstOrCreate(
                    ['user_id' => $user->id, 'investment_id' => $investment->id, 'earned_on' => $period->toDateString(), 'source' => 'mining_return'],
                    ['amount' => $amount, 'status' => 'available', 'notes' => 'Monthly mining return generated for '.$period->format('F Y').'.'],
                );
            });
    }

    public static function awardReferralRegistration(User $registeredUser): Collection
    {
        return FriendInvitation::query()->with('user')->where('email', $registeredUser->email)->get()->map(function (FriendInvitation $invitation) use ($registeredUser) {
            $reward = Earning::firstOrCreate(
                ['user_id' => $invitation->user_id, 'investment_id' => null, 'earned_on' => now()->toDateString(), 'source' => 'referral_registration', 'notes' => 'Referral registration reward for '.$registeredUser->email.'.'],
                ['amount' => (float) self::rewardSetting('referral_registration_reward'), 'status' => 'pending'],
            );

            self::syncReferralRegistrationRewards($invitation->user->fresh());
            self::maybeCelebrateProfilePower($invitation->user->fresh());

            return $reward->fresh();
        });
    }

    public static function awardReferralSubscription(User $referredUser, UserInvestment $investment): Collection
    {
        $rewardAmount = round((float) $investment->amount * (float) self::rewardSetting('referral_subscription_reward_rate'), 2);

        $rewards = FriendInvitation::query()->with('user')->where('email', $referredUser->email)->get()->map(function (FriendInvitation $invitation) use ($referredUser, $investment, $rewardAmount) {
            self::maybeCelebrateProfilePower($invitation->user->fresh());

            return Earning::firstOrCreate(
                ['user_id' => $invitation->user_id, 'investment_id' => null, 'earned_on' => now()->toDateString(), 'source' => 'referral_subscription', 'notes' => 'Referral subscription reward for '.$referredUser->email.' on investment #'.$investment->id.'.'],
                ['amount' => $rewardAmount, 'status' => 'available'],
            );
        });

        self::awardTeamSubscriptionRewards($referredUser, $investment);

        return $rewards;
    }

    public static function syncReferralRegistrationRewards(User $user): void
    {
        $user->loadMissing(['investments.package', 'sponsoredUsers']);

        $allRegistrationRewards = $user->earnings()
            ->where('source', 'referral_registration')
            ->orderBy('earned_on')
            ->orderBy('id')
            ->get();

        if ($allRegistrationRewards->isEmpty()) {
            return;
        }

        $committedStatuses = ['paid', 'payout_pending'];
        $adjustableRewards = $allRegistrationRewards->whereNotIn('status', $committedStatuses)->values();
        $committedAmount = (float) $allRegistrationRewards->whereIn('status', $committedStatuses)->sum('amount');
        $totalRewardAmount = (float) $allRegistrationRewards->sum('amount');

        $unlockCap = self::eligibleReferralRegistrationUnlockCap($user);
        $availableTarget = max(min($totalRewardAmount, $unlockCap) - $committedAmount, 0);

        DB::transaction(function () use ($adjustableRewards, $availableTarget) {
            $remainingAvailable = round($availableTarget, 2);

            foreach ($adjustableRewards as $reward) {
                $rewardAmount = round((float) $reward->amount, 2);

                if ($rewardAmount <= 0) {
                    continue;
                }

                if ($remainingAvailable >= $rewardAmount) {
                    if ($reward->status !== 'available') {
                        $reward->forceFill(['status' => 'available'])->save();
                    }

                    $remainingAvailable = round($remainingAvailable - $rewardAmount, 2);
                    continue;
                }

                if ($remainingAvailable <= 0) {
                    if ($reward->status !== 'pending') {
                        $reward->forceFill(['status' => 'pending'])->save();
                    }

                    continue;
                }

                $pendingAmount = round($rewardAmount - $remainingAvailable, 2);

                $reward->forceFill([
                    'amount' => $remainingAvailable,
                    'status' => 'available',
                ])->save();

                Earning::create([
                    'user_id' => $reward->user_id,
                    'investment_id' => $reward->investment_id,
                    'payout_request_id' => null,
                    'earned_on' => $reward->earned_on,
                    'amount' => $pendingAmount,
                    'source' => $reward->source,
                    'status' => 'pending',
                    'notes' => $reward->notes,
                ]);

                $remainingAvailable = 0;
            }
        });
    }

    public static function syncReferralRegistrationRewardsForUserAndAncestors(User $user, int $maxDepth = 3): void
    {
        $currentUser = $user->fresh();
        $depth = 0;

        while ($currentUser && $depth <= $maxDepth) {
            self::syncReferralRegistrationRewards($currentUser);
            $currentUser = $currentUser->sponsor()->first();
            $depth++;
        }
    }

    public static function eligibleReferralRegistrationUnlockCap(User $user): float
    {
        if (! self::hasActiveBasic100Investment($user)) {
            return 0.0;
        }

        $treeInvestmentVolume = self::referralTreeInvestmentVolume($user, 3);

        if ($treeInvestmentVolume <= 0) {
            return 0.0;
        }

        return round($treeInvestmentVolume * 0.5, 2);
    }

    public static function hasActiveBasic100Investment(User $user): bool
    {
        if ($user->relationLoaded('investments')) {
            return $user->investments
                ->where('status', 'active')
                ->contains(fn (UserInvestment $investment) => $investment->package?->slug === self::BASIC_UPGRADE_PACKAGE_SLUG);
        }

        return $user->investments()
            ->where('status', 'active')
            ->whereHas('package', fn ($query) => $query->where('slug', self::BASIC_UPGRADE_PACKAGE_SLUG))
            ->exists();
    }

    public static function referralTreeInvestmentVolume(User $user, int $maxDepth = 3): float
    {
        $treeUserIds = self::referralTreeUserIds($user, $maxDepth);

        if ($treeUserIds->isEmpty()) {
            return 0.0;
        }

        return round((float) UserInvestment::query()
            ->whereIn('user_id', $treeUserIds)
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->sum('amount'), 2);
    }

    public static function referralTreeUserIds(User $user, int $maxDepth = 3): Collection
    {
        $currentLevelIds = collect([$user->id]);
        $treeIds = collect();

        foreach (range(1, $maxDepth) as $depth) {
            $nextLevelIds = User::query()
                ->whereIn('sponsor_user_id', $currentLevelIds)
                ->pluck('id');

            if ($nextLevelIds->isEmpty()) {
                break;
            }

            $treeIds = $treeIds->merge($nextLevelIds);
            $currentLevelIds = $nextLevelIds;
        }

        return $treeIds->unique()->values();
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
                $refreshedSponsor = self::refreshInvestmentBonusRates($currentSponsor->fresh());
                self::attemptStarterUpgrade($refreshedSponsor->fresh());
                self::maybeCelebrateProfilePower($refreshedSponsor->fresh());
            }

            self::maybeCelebrateProfilePower($currentSponsor->fresh());

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
                throw new RuntimeException('You can only withdraw from available earnings. Invested capital and share amounts are not withdrawable.');
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

    protected static function mapReferralTreeNodes(Collection $usersBySponsor, string|int $parentKey, int $depth, int $maxDepth): Collection
    {
        return collect($usersBySponsor->get($parentKey, collect()))
            ->map(fn (User $user) => self::mapReferralTreeNode($usersBySponsor, $user, $depth, $maxDepth))
            ->map(function (array $node) {
                $node['situation'] = self::treeNodeSituation($node);

                return $node;
            })
            ->values();
    }

    protected static function mapReferralTreeNode(Collection $usersBySponsor, User $user, int $depth, int $maxDepth): array
    {
        $children = $depth < $maxDepth
            ? self::mapReferralTreeNodes($usersBySponsor, $user->id, $depth + 1, $maxDepth)
            : collect();

        $activeInvestments = $user->relationLoaded('investments')
            ? $user->investments->where('status', 'active')->where('amount', '>', 0)
            : $user->investments()->where('status', 'active')->where('amount', '>', 0)->get();

        return [
            'user' => $user,
            'depth' => $depth,
            'level_name' => $user->userLevel?->name ?? 'Starter',
            'sponsor_name' => $user->sponsor?->name ?? 'Top-level',
            'verified_invites' => $user->relationLoaded('friendInvitations')
                ? $user->friendInvitations->whereNotNull('verified_at')->count()
                : 0,
            'active_capital' => (float) $activeInvestments->sum('amount'),
            'active_shares' => (int) $activeInvestments->sum('shares_owned'),
            'direct_team' => (int) collect($usersBySponsor->get($user->id, collect()))->count(),
            'active_direct_investors' => self::activeDirectInvestorCount($user),
            'children' => $children->values(),
            'children_count' => $children->count(),
            'visible_descendants' => (int) $children->sum(fn (array $child) => 1 + $child['visible_descendants']),
            'branch_active_capital' => (float) ((float) $activeInvestments->sum('amount') + $children->sum('branch_active_capital')),
            'branch_active_investors' => (int) ($activeInvestments->isNotEmpty() ? 1 : 0) + (int) $children->sum('branch_active_investors'),
            'power_summary' => self::compactTreePowerSummary($user),
            'reward_caps' => self::unlockedRewardCapBadges($user),
        ];
    }

    protected static function flattenReferralTree(Collection $tree): Collection
    {
        return $tree->flatMap(function (array $node) {
            return collect([$node])->merge(self::flattenReferralTree($node['children']));
        })->values();
    }

    protected static function appendReferralTreeChartNode(array $node, string $parentId, array &$nodes, array &$links): void
    {
        $nodeId = 'user-'.$node['user']->id;

        $nodes[] = [
            'id' => $nodeId,
            'title' => $node['user']->name,
            'name' => $node['situation']['label'],
            'color' => match (true) {
                $node['depth'] === 1 => '#f59e0b',
                $node['depth'] === 2 => '#ef4444',
                $node['depth'] === 3 => '#4f46e5',
                default => '#0891b2',
            },
            'situation' => $node['situation']['description'],
            'priority' => $node['situation']['priority'],
            'level_name' => $node['level_name'],
            'sponsor_name' => $node['sponsor_name'],
            'power' => $node['power_summary']['score'].'/100',
            'direct_team' => (string) $node['direct_team'],
            'active_direct' => (string) $node['active_direct_investors'],
            'capital' => '$'.number_format($node['active_capital'], 2),
            'verified_invites' => (string) $node['verified_invites'],
            'profile_url' => route('dashboard.investors.show', [
                'user' => $node['user'],
                'from' => request()->routeIs('dashboard.network-admin') ? 'network-admin' : 'network',
            ]),
        ];

        $links[] = [$parentId, $nodeId];

        foreach ($node['children'] as $child) {
            self::appendReferralTreeChartNode($child, $nodeId, $nodes, $links);
        }
    }

    public static function logAdminActivity(User $admin, string $action, string $summary, ?Model $subject = null, array $details = []): AdminActivityLog
    {
        return AdminActivityLog::create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'summary' => Str::limit($summary, 255),
            'details' => $details,
            'ip_address' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 1000),
        ]);
    }
}


































