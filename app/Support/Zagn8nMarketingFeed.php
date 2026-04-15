<?php

namespace App\Support;

use App\Models\InvestmentPackage;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\UserInvestment;
use Illuminate\Support\Carbon;

class Zagn8nMarketingFeed
{
    public static function payload(): array
    {
        MiningPlatform::ensureDefaults();

        $last24Hours = Carbon::now()->subDay();
        $last7Days = Carbon::now()->subDays(7);

        $activeInvestments = UserInvestment::query()
            ->where('status', 'active')
            ->where('amount', '>', 0);

        $paidPayouts = PayoutRequest::query()->where('status', 'paid');

        $topPackages = InvestmentPackage::query()
            ->with(['miner', 'investments' => fn ($query) => $query->where('status', 'active')->where('amount', '>', 0)])
            ->where('is_active', true)
            ->get()
            ->map(function (InvestmentPackage $package) {
                $investments = $package->investments;
                $investorCount = $investments->pluck('user_id')->unique()->count();
                $capital = round((float) $investments->sum('amount'), 2);

                return [
                    'slug' => $package->slug,
                    'name' => $package->name,
                    'miner' => $package->miner?->name,
                    'price' => round((float) $package->price, 2),
                    'shares_count' => (int) $package->shares_count,
                    'monthly_return_rate_percent' => round((float) $package->monthly_return_rate * 100, 2),
                    'active_investor_count' => $investorCount,
                    'active_capital_usd' => $capital,
                ];
            })
            ->sortByDesc('active_capital_usd')
            ->take(3)
            ->values()
            ->all();

        return [
            'feed' => 'zagn8n_marketing_safe_metrics',
            'generated_at' => now()->toIso8601String(),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
            'totals' => [
                'active_investor_count' => (clone $activeInvestments)->distinct('user_id')->count('user_id'),
                'active_capital_usd' => round((float) (clone $activeInvestments)->sum('amount'), 2),
                'verified_user_count' => User::query()->whereNotNull('email_verified_at')->count(),
                'approved_kyc_count' => User::query()->where('kyc_status', 'approved')->count(),
                'pending_kyc_count' => User::query()->where('kyc_status', 'pending')->count(),
                'paid_payout_count' => (clone $paidPayouts)->count(),
                'paid_payout_total_usd' => round((float) (clone $paidPayouts)->sum('net_amount'), 2),
            ],
            'last_24_hours' => [
                'new_registrations' => User::query()->where('created_at', '>=', $last24Hours)->count(),
                'new_active_investments' => UserInvestment::query()->where('status', 'active')->where('amount', '>', 0)->where('subscribed_at', '>=', $last24Hours)->count(),
                'new_active_capital_usd' => round((float) UserInvestment::query()->where('status', 'active')->where('amount', '>', 0)->where('subscribed_at', '>=', $last24Hours)->sum('amount'), 2),
                'kyc_submissions' => User::query()->where('kyc_submitted_at', '>=', $last24Hours)->count(),
                'payout_requests' => PayoutRequest::query()->where('requested_at', '>=', $last24Hours)->count(),
                'paid_payouts' => PayoutRequest::query()->where('status', 'paid')->where('processed_at', '>=', $last24Hours)->count(),
            ],
            'last_7_days' => [
                'new_registrations' => User::query()->where('created_at', '>=', $last7Days)->count(),
                'new_active_investments' => UserInvestment::query()->where('status', 'active')->where('amount', '>', 0)->where('subscribed_at', '>=', $last7Days)->count(),
                'new_active_capital_usd' => round((float) UserInvestment::query()->where('status', 'active')->where('amount', '>', 0)->where('subscribed_at', '>=', $last7Days)->sum('amount'), 2),
                'approved_kyc' => User::query()->where('kyc_status', 'approved')->where('kyc_reviewed_at', '>=', $last7Days)->count(),
                'paid_payouts' => PayoutRequest::query()->where('status', 'paid')->where('processed_at', '>=', $last7Days)->count(),
                'paid_payout_total_usd' => round((float) PayoutRequest::query()->where('status', 'paid')->where('processed_at', '>=', $last7Days)->sum('net_amount'), 2),
            ],
            'top_packages' => $topPackages,
            'content_rules' => [
                'use_only_real_metrics' => true,
                'avoid_guaranteed_profit_language' => true,
                'avoid_fake_urgency' => true,
                'prefer_simple_premium_tone' => true,
                'focus_on_clarity_trust_and_dashboard_visibility' => true,
            ],
        ];
    }
}
