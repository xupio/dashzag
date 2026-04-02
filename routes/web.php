<?php

use App\Http\Controllers\InternalMailController;
use App\Http\Controllers\AdminTwoFactorController;
use App\Http\Controllers\ProfileController;
use App\Rules\AllowedFileSignature;
use App\Mail\FriendInvitationMail;
use App\Models\AdminActivityLog;
use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\HallOfFameSnapshot;
use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\MockManagerScenario;
use App\Models\PayoutRequest;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
use App\Models\User;
use App\Models\UserLoginEvent;
use App\Models\UserInvestment;
use App\Models\UserPageActivityLog;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\DigestSummaryNotification;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;
use App\Support\UserActivity;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

$marketingPageData = function () {
    MiningPlatform::ensureDefaults();

    $featuredMiner = MiningPlatform::resolveMiner(null);
    $featuredMiner->load([
        'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order'),
        'performanceLogs' => fn ($query) => $query->latest('logged_on')->limit(6),
    ]);

    $packages = $featuredMiner->packages;
    $sharesSold = MiningPlatform::totalSharesSold($featuredMiner);
    $availableShares = max($featuredMiner->total_shares - $sharesSold, 0);

    return [
        'featuredMiner' => $featuredMiner,
        'packages' => $packages,
        'sharesSold' => $sharesSold,
        'availableShares' => $availableShares,
        'mediaGallery' => collect([
            [
                'title' => 'Alpha One infrastructure',
                'type' => 'image',
                'description' => 'A visual snapshot of the cloud-mining operation, built for investor confidence.',
                'src' => asset('build/images/others/placeholder.jpg'),
            ],
            [
                'title' => 'Product walkthrough',
                'type' => 'video',
                'description' => 'A dedicated explainer slot for your subscription walkthrough and onboarding video.',
                'src' => null,
            ],
            [
                'title' => 'Animated reward flow',
                'type' => 'motion',
                'description' => 'An animated overview of shares, monthly return, and referral growth inside the platform.',
                'src' => null,
            ],
        ]),
        'references' => collect([
            [
                'name' => 'Infrastructure operations team',
                'role' => 'Mining supervision',
                'quote' => 'The platform is structured to translate miner performance into a clear subscription and earnings story.',
            ],
            [
                'name' => 'Early investor group',
                'role' => 'Pilot shareholders',
                'quote' => 'The package structure and dashboard visibility make the business understandable even for first-time investors.',
            ],
            [
                'name' => 'Referral growth partners',
                'role' => 'Network expansion',
                'quote' => 'The referral engine gives the product long-term momentum beyond a simple subscription page.',
            ],
        ]),
        'faqItems' => collect([
            [
                'question' => 'What does a package represent?',
                'answer' => 'Each package represents a number of shares inside the featured miner, and the return follows the miner base rate plus package uplift.',
            ],
            [
                'question' => 'How do referrals help the investor?',
                'answer' => 'Users can unlock upgrades, earn referral rewards, and improve their own return rate as their verified network and active investor team grow.',
            ],
            [
                'question' => 'How is payment handled?',
                'answer' => 'Users submit a payment order, upload proof after transfer, and the operations team reviews it before activating the investment.',
            ],
        ]),
    ];
};

Route::get('/', function () use ($marketingPageData) {
    return view('marketing.home', $marketingPageData());
})->name('landing');

Route::get('/about', function () use ($marketingPageData) {
    return view('marketing.about', $marketingPageData());
})->name('marketing.about');

Route::get('/how-it-works', function () use ($marketingPageData) {
    return view('marketing.about', $marketingPageData());
})->name('marketing.how-it-works');

Route::redirect('/packages', '/');
Route::redirect('/media', '/');
Route::redirect('/references', '/');

Route::get('/friend-invitations/{friendInvitation}/verify', function (FriendInvitation $friendInvitation) {
    if (! $friendInvitation->verified_at) {
        $friendInvitation->forceFill(['verified_at' => now()])->save();
    }

    return view('friend-invitations.verified', [
        'friendInvitation' => $friendInvitation,
    ]);
})->middleware(['signed', 'throttle:6,1'])->name('friend-invitations.verify');

Route::get('/dashboard', function (Request $request) {
    MiningPlatform::ensureDefaults();

    $rewardCapBadgesForUser = function (User $targetUser): array {
        $rewardCapUnlocks = $targetUser->notifications()
            ->where('type', ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
            ->pluck('data.reward_cap_tier')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $badges = [];

        if (in_array('basic', $rewardCapUnlocks, true)) {
            $badges[] = ['label' => '4% cap', 'class' => 'bg-primary-subtle text-primary'];
        }

        if (in_array('growth', $rewardCapUnlocks, true)) {
            $badges[] = ['label' => '6% cap', 'class' => 'bg-info-subtle text-info'];
        }

        if (in_array('scale', $rewardCapUnlocks, true)) {
            $badges[] = ['label' => '7% cap', 'class' => 'bg-dark text-white'];
        }

        return $badges;
    };

    $user = $request->user()->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations']);
    $level = MiningPlatform::syncUserLevel($user);
    $user->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations']);

    $miners = MiningPlatform::activeMiners();
    $miner = MiningPlatform::resolveMiner($request->query('miner'));
    $miner->load(['performanceLogs', 'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')]);

    $minerPerformanceSummary = MiningPlatform::minerPerformanceSummary($miner, 7);
    $performanceLogs = $minerPerformanceSummary['logs'];
    $recentPerformanceLogs = $miner->performanceLogs()->orderByDesc('logged_on')->limit(5)->get();
    $sharesSold = MiningPlatform::totalSharesSold($miner);
    $availableShares = max($miner->total_shares - $sharesSold, 0);

    $packageShareBreakdown = UserInvestment::query()
        ->with('package')
        ->where('miner_id', $miner->id)
        ->where('status', 'active')
        ->get()
        ->groupBy('package_id')
        ->map(function ($investments) use ($miner) {
            $package = $investments->first()?->package;
            $shares = (int) $investments->sum('shares_owned');

            return [
                'label' => $package?->name ?? 'Active package',
                'shares' => $shares,
                'investors' => $investments->pluck('user_id')->unique()->count(),
                'capital' => round((float) $investments->sum('amount'), 2),
                'utilization' => $miner->total_shares > 0 ? round(($shares / $miner->total_shares) * 100, 2) : 0,
                'type' => 'package',
            ];
        })
        ->filter(fn ($segment) => $segment['shares'] > 0)
        ->sortByDesc('shares')
        ->values();

    $shareStatusBreakdown = $packageShareBreakdown->values();

    if ($availableShares > 0) {
        $shareStatusBreakdown = $shareStatusBreakdown->push([
            'label' => 'Available shares',
            'shares' => $availableShares,
            'investors' => 0,
            'capital' => 0,
            'utilization' => $miner->total_shares > 0 ? round(($availableShares / $miner->total_shares) * 100, 2) : 0,
            'type' => 'available',
        ]);
    }

    $minerInvestorPipeline = UserInvestment::query()
        ->with(['user.userLevel', 'user.friendInvitations', 'user.sponsoredUsers.investments', 'user.investments', 'package'])
        ->where('miner_id', $miner->id)
        ->where('status', 'active')
        ->orderByDesc('subscribed_at')
        ->get()
        ->groupBy('user_id')
        ->map(function ($investments) use ($rewardCapBadgesForUser) {
            $latestInvestment = $investments->sortByDesc(fn ($investment) => optional($investment->subscribed_at)->timestamp ?? 0)->first();
            $investor = $latestInvestment?->user;

            return [
                'user' => $investor,
                'package_name' => $latestInvestment?->package?->name ?? 'Active package',
                'shares_owned' => (int) $investments->sum('shares_owned'),
                'capital_committed' => round((float) $investments->sum('amount'), 2),
                'expected_return_rate' => round(((float) $latestInvestment?->monthly_return_rate + (float) $latestInvestment?->level_bonus_rate + (float) $latestInvestment?->team_bonus_rate) * 100, 2),
                'active_positions' => $investments->count(),
                'latest_subscribed_at' => $latestInvestment?->subscribed_at,
                'profile_power' => $investor ? MiningPlatform::profilePowerSummary($investor) : null,
                'reward_cap_badges' => $investor ? $rewardCapBadgesForUser($investor) : [],
            ];
        })
        ->filter(fn ($row) => $row['user'])
        ->sortByDesc(fn ($row) => optional($row['latest_subscribed_at'])->timestamp ?? 0)
        ->values();

    $weeklyHallOfFameWinner = MiningPlatform::competitionLeaderboard('weekly', 1)->first();
    $monthlyHallOfFameChampion = MiningPlatform::competitionLeaderboard('monthly', 1)->first();

    return view('dashboard', [
        'user' => $user,
        'miners' => $miners,
        'miner' => $miner,
        'level' => $level,
        'starterPackage' => MiningPlatform::freeStarterPackage(),
        'starterProgress' => MiningPlatform::starterUpgradeProgress($user),
        'sharesSold' => $sharesSold,
        'availableShares' => $availableShares,
        'activeInvestment' => $user->investments->where('status', 'active')->firstWhere('miner_id', $miner->id),
        'performanceLabels' => $performanceLogs->map(fn ($log) => $log->logged_on->format('M d'))->values(),
        'performanceRevenueData' => $performanceLogs->map(fn ($log) => round((float) $log->revenue_usd, 2))->values(),
        'performanceCostData' => $performanceLogs->map(fn ($log) => round((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2))->values(),
        'performanceNetProfitData' => $performanceLogs->map(fn ($log) => round((float) $log->net_profit_usd, 2))->values(),
        'performanceHashrateData' => $performanceLogs->map(fn ($log) => round((float) $log->hashrate_th, 2))->values(),
        'performanceUptimeData' => $performanceLogs->map(fn ($log) => round((float) $log->uptime_percentage, 2))->values(),
        'recentPerformanceLogs' => $recentPerformanceLogs,
        'livePerformanceSummary' => $minerPerformanceSummary,
        'shareStatusLabels' => $shareStatusBreakdown->pluck('label')->values()->all(),
        'shareStatusSeries' => $shareStatusBreakdown->pluck('shares')->values()->all(),
        'shareStatusDetails' => $shareStatusBreakdown->values()->all(),
        'minerInvestorPipeline' => $minerInvestorPipeline,
        'weeklyHallOfFameWinnerId' => $weeklyHallOfFameWinner['user']->id ?? null,
        'monthlyHallOfFameChampionId' => $monthlyHallOfFameChampion['user']->id ?? null,
    ]);
})->middleware(['auth', 'verified', 'admin.two_factor', 'single_session'])->name('dashboard');

Route::middleware(['auth', 'verified', 'admin.two_factor', 'single_session'])->group(function () {
    Route::post('/dashboard/activity/page-visit', function (Request $request) {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'seconds_spent' => ['required', 'integer', 'min:1', 'max:86400'],
        ]);

        UserActivity::recordPageVisit($request->user(), $request, $validated);

        return response()->noContent();
    })->name('dashboard.activity.page-visit');

    Route::get('/dashboard/miner-report', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load(['investments.package', 'investments.miner', 'earnings.investment.miner']);
        $miners = MiningPlatform::activeMiners();
        $miner = MiningPlatform::resolveMiner($request->query('miner'));
        $miner->load([
            'performanceLogs' => fn ($query) => $query->orderByDesc('logged_on')->limit(14),
            'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order'),
        ]);

        $minerPerformanceSummary = MiningPlatform::minerPerformanceSummary($miner, 14);
        $performanceLogs = $minerPerformanceSummary['logs'];
        $activeInvestments = $user->investments->where('status', 'active')->where('miner_id', $miner->id)->values();
        $userMinerEarnings = $user->earnings
            ->filter(fn ($earning) => $earning->source === 'mining_daily_share' && $earning->investment?->miner_id === $miner->id)
            ->sortByDesc(fn ($earning) => optional($earning->earned_on)?->timestamp ?? 0)
            ->values();

        $userMinerEarningsByDay = $userMinerEarnings
            ->take(14)
            ->groupBy(fn ($earning) => optional($earning->earned_on)?->toDateString() ?? 'unknown')
            ->map(function ($earnings, $date) {
                return [
                    'label' => Carbon::parse($date)->format('M d'),
                    'total' => round((float) $earnings->sum('amount'), 2),
                ];
            })
            ->sortBy('label')
            ->values();

        return view('pages.general.miner-report', [
            'user' => $user,
            'miners' => $miners,
            'miner' => $miner,
            'minerPerformanceSummary' => $minerPerformanceSummary,
            'performanceLogs' => $performanceLogs,
            'activeInvestmentCount' => $activeInvestments->count(),
            'activeSharesOwned' => (int) $activeInvestments->sum('shares_owned'),
            'activeCapital' => round((float) $activeInvestments->sum('amount'), 2),
            'latestUserMinerPayout' => (float) optional($userMinerEarnings->first())->amount,
            'userMinerPayoutTotal' => round((float) $userMinerEarnings->take(14)->sum('amount'), 2),
            'userMinerEarningsByDay' => $userMinerEarningsByDay,
            'userHasStake' => $activeInvestments->isNotEmpty(),
        ]);
    })->name('dashboard.miner-report');

    Route::get('/dashboard/miner-report/export', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load(['investments.miner', 'earnings.investment.miner']);
        $miner = MiningPlatform::resolveMiner($request->query('miner'));
        $performanceLogs = $miner->performanceLogs()
            ->orderByDesc('logged_on')
            ->limit(14)
            ->get()
            ->sortBy(fn ($log) => optional($log->logged_on)?->timestamp ?? 0)
            ->values();

        $userMinerEarnings = $user->earnings
            ->filter(fn ($earning) => $earning->source === 'mining_daily_share' && $earning->investment?->miner_id === $miner->id)
            ->sortBy(fn ($earning) => optional($earning->earned_on)?->timestamp ?? 0)
            ->values();

        $filename = $miner->slug.'-daily-miner-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($miner, $performanceLogs, $userMinerEarnings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Label', 'Value']);
            fputcsv($handle, ['Summary', 'Miner', $miner->name]);
            fputcsv($handle, ['Summary', 'Slug', $miner->slug]);
            fputcsv($handle, ['Summary', 'Tracked days', $performanceLogs->count()]);
            fputcsv($handle, []);
            fputcsv($handle, ['Daily log', 'Date', 'Revenue', 'Costs', 'Net profit', 'Per share', 'Hashrate', 'Uptime', 'Source']);

            foreach ($performanceLogs as $log) {
                fputcsv($handle, [
                    'Daily log',
                    optional($log->logged_on)->format('Y-m-d'),
                    number_format((float) $log->revenue_usd, 2, '.', ''),
                    number_format((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2, '.', ''),
                    number_format((float) $log->net_profit_usd, 2, '.', ''),
                    number_format((float) $log->revenue_per_share_usd, 4, '.', ''),
                    number_format((float) $log->hashrate_th, 2, '.', ''),
                    number_format((float) $log->uptime_percentage, 2, '.', ''),
                    $log->source,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Your payout', 'Date', 'Amount', 'Notes']);

            foreach ($userMinerEarnings as $earning) {
                fputcsv($handle, [
                    'Your payout',
                    optional($earning->earned_on)->format('Y-m-d'),
                    number_format((float) $earning->amount, 2, '.', ''),
                    $earning->notes,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    })->name('dashboard.miner-report.export');

    Route::get('/dashboard/miner-report/print', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load(['investments.package', 'investments.miner', 'earnings.investment.miner']);
        $miners = MiningPlatform::activeMiners();
        $miner = MiningPlatform::resolveMiner($request->query('miner'));
        $miner->load([
            'performanceLogs' => fn ($query) => $query->orderByDesc('logged_on')->limit(14),
            'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order'),
        ]);

        $minerPerformanceSummary = MiningPlatform::minerPerformanceSummary($miner, 14);
        $performanceLogs = $minerPerformanceSummary['logs'];
        $activeInvestments = $user->investments->where('status', 'active')->where('miner_id', $miner->id)->values();
        $userMinerEarnings = $user->earnings
            ->filter(fn ($earning) => $earning->source === 'mining_daily_share' && $earning->investment?->miner_id === $miner->id)
            ->sortByDesc(fn ($earning) => optional($earning->earned_on)?->timestamp ?? 0)
            ->values();

        return view('pages.general.miner-report-print', [
            'user' => $user,
            'miners' => $miners,
            'miner' => $miner,
            'minerPerformanceSummary' => $minerPerformanceSummary,
            'performanceLogs' => $performanceLogs,
            'activeInvestmentCount' => $activeInvestments->count(),
            'activeSharesOwned' => (int) $activeInvestments->sum('shares_owned'),
            'activeCapital' => round((float) $activeInvestments->sum('amount'), 2),
            'latestUserMinerPayout' => (float) optional($userMinerEarnings->first())->amount,
            'userMinerPayoutTotal' => round((float) $userMinerEarnings->take(14)->sum('amount'), 2),
            'userMinerEarnings' => $userMinerEarnings->take(14),
            'userHasStake' => $activeInvestments->isNotEmpty(),
        ]);
    })->name('dashboard.miner-report.print');

    Route::get('/email/inbox', [InternalMailController::class, 'inbox'])->name('email.inbox');
    Route::get('/email/starred', [InternalMailController::class, 'starred'])->name('email.starred');
    Route::get('/email/archived', [InternalMailController::class, 'archived'])->name('email.archived');
    Route::get('/email/trash', [InternalMailController::class, 'trash'])->name('email.trash');
    Route::get('/email/drafts', [InternalMailController::class, 'drafts'])->name('email.drafts');
    Route::get('/email/sent', [InternalMailController::class, 'sent'])->name('email.sent');
    Route::get('/email/compose', [InternalMailController::class, 'compose'])->name('email.compose');
    Route::post('/email/send', [InternalMailController::class, 'store'])->middleware('throttle:12,1')->name('email.store');
    Route::post('/email/{message}/reply', [InternalMailController::class, 'reply'])->middleware('throttle:20,1')->name('email.reply');
    Route::get('/email/attachments/{attachment}/download', [InternalMailController::class, 'downloadAttachment'])->name('email.attachments.download');
    Route::post('/email/drafts/{draft}/attachments/{attachment}/remove', [InternalMailController::class, 'removeDraftAttachment'])->middleware('throttle:30,1')->name('email.draft-attachments.remove');
    Route::post('/email/drafts/{draft}/delete', [InternalMailController::class, 'deleteDraft'])->middleware('throttle:20,1')->name('email.drafts.delete');
    Route::get('/email/inbox/{recipient}/read', [InternalMailController::class, 'showInbox'])->name('email.read');
    Route::post('/email/inbox/{recipient}/toggle-star', [InternalMailController::class, 'toggleStar'])->middleware('throttle:40,1')->name('email.toggle-star');
    Route::post('/email/inbox/{recipient}/toggle-read', [InternalMailController::class, 'toggleRead'])->middleware('throttle:40,1')->name('email.toggle-read');
    Route::post('/email/inbox/{recipient}/archive', [InternalMailController::class, 'archive'])->middleware('throttle:30,1')->name('email.archive');
    Route::post('/email/inbox/{recipient}/delete', [InternalMailController::class, 'deleteRecipientMessage'])->middleware('throttle:30,1')->name('email.delete');
    Route::post('/email/inbox/{recipient}/restore', [InternalMailController::class, 'restoreRecipientMessage'])->middleware('throttle:30,1')->name('email.restore');
    Route::post('/email/inbox/{recipient}/purge', [InternalMailController::class, 'purgeRecipientMessage'])->middleware('throttle:15,1')->name('email.purge');
    Route::post('/email/inbox/bulk-action', [InternalMailController::class, 'bulkMailboxAction'])->middleware('throttle:10,1')->name('email.bulk');
    Route::get('/email/sent/{message}/read', [InternalMailController::class, 'showSent'])->name('email.sent.read');

    Route::get('/dashboard/profile', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations', 'earnings']);
        $level = MiningPlatform::syncUserLevel($user);
        $user->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations', 'earnings']);
        $starterProgress = MiningPlatform::starterUpgradeProgress($user);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $totalInvested = (float) $activeInvestments->sum('amount');
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
        MiningPlatform::syncMiningDailyShareUnlocks($user);
        $user->load('earnings');
        $walletSummary = MiningPlatform::walletSummary($user);
        $availableEarnings = (float) ($walletSummary['available'] ?? 0);
        $pendingReferrals = $user->friendInvitations->whereNull('verified_at')->count();
        $verifiedReferrals = $user->friendInvitations->whereNotNull('verified_at')->count();
        $registeredReferrals = $user->friendInvitations->whereNotNull('registered_at')->count();
        $teamBonusRate = MiningPlatform::teamBonusRate($user);
        $profilePower = MiningPlatform::profilePowerSummary($user);
        $weeklyMomentum = MiningPlatform::weeklyMomentumSummary($user);
        $monthlyMomentum = MiningPlatform::monthlyMomentumSummary($user);
        $weeklyMomentumHistory = MiningPlatform::weeklyMomentumHistory($user);
        $leaderboard = MiningPlatform::profilePowerLeaderboard();
        $leaderboardPosition = $leaderboard->search(fn ($row) => $row['user']->is($user));
        $recentRankCelebrations = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_rank')
            ->take(3)
            ->values();
        $recentRewardCapCelebrations = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
            ->take(3)
            ->values();
        $recentHallOfFameWins = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => in_array(($notification->data['event_key'] ?? null), ['hall_of_fame_weekly_winner', 'hall_of_fame_monthly_winner'], true))
            ->take(4)
            ->values();
        $hallOfFameWinCounts = [
            'weekly' => HallOfFameSnapshot::query()->where('user_id', $user->id)->where('category', 'weekly')->where('rank_position', 1)->count(),
            'monthly' => HallOfFameSnapshot::query()->where('user_id', $user->id)->where('category', 'monthly')->where('rank_position', 1)->count(),
        ];
        $investmentAllocation = $activeInvestments
            ->groupBy(fn ($investment) => $investment->miner?->name ?? 'Unknown miner')
            ->map(fn ($investments) => round((float) $investments->sum('amount'), 2))
            ->sortDesc();
        $rewardCapUnlocksByTier = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
            ->keyBy(fn ($notification) => $notification->data['reward_cap_tier'] ?? '');
        $publicRewardCapSummary = collect([
            [
                'label' => 'Basic 100',
                'amount_label' => '$100 tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_basic_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_basic_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('basic'),
            ],
            [
                'label' => 'Growth 500',
                'amount_label' => '$500 tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_growth_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_growth_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('growth'),
            ],
            [
                'label' => 'Scale 1000+',
                'amount_label' => '$1000+ tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_scale_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_scale_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('scale'),
            ],
        ])->all();
        $profilePowerPointsToFullCap = max(100 - (int) $profilePower['score'], 0);
        $profilePowerNextActionLabels = collect($profilePower['recommended_actions'] ?? [])
            ->take(2)
            ->pluck('title')
            ->values()
            ->all();
        $profileInvestmentStatus = $activeInvestments
            ->where('amount', '>', 0)
            ->map(function ($investment) use ($user) {
                $investmentEarnings = $user->earnings->where('investment_id', $investment->id);
                $unlockDate = $investment->subscribed_at?->copy()?->addDays(30);

                return [
                    'investment_id' => $investment->id,
                    'package_name' => $investment->package?->name ?? 'Package',
                    'subscribed_at' => $investment->subscribed_at,
                    'unlock_date' => $unlockDate,
                    'is_unlocked' => $unlockDate ? now()->greaterThanOrEqualTo($unlockDate) : false,
                    'days_remaining' => $unlockDate && now()->lt($unlockDate) ? now()->diffInDays($unlockDate) : 0,
                    'available_amount' => round((float) $investmentEarnings->where('status', 'available')->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'locked_amount' => round((float) $investmentEarnings->whereIn('status', ['pending', 'payout_pending'])->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'projected_amount' => round((float) $investmentEarnings->where('source', 'projected_return')->sum('amount'), 2),
                    'daily_cap' => MiningPlatform::investmentBaseDailyShareCap($investment),
                    'monthly_cap' => round(((float) $investment->amount) * (float) ($investment->package?->monthly_return_rate ?? $investment->monthly_return_rate), 2),
                ];
            })
            ->values();
        $nextInvestmentUnlock = $profileInvestmentStatus
            ->where('is_unlocked', false)
            ->sortBy(fn ($row) => optional($row['unlock_date'])->timestamp ?? PHP_INT_MAX)
            ->first();
        $profileRewardBoostSummary = collect([
            [
                'label' => 'Basic 100',
                'amount_label' => '$100 tier',
                'eligible_count' => $activeInvestments->filter(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500)->count(),
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_basic_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_basic_max_rate'),
                'points_to_full_cap' => $profilePowerPointsToFullCap,
                'next_actions' => $profilePowerNextActionLabels,
            ],
            [
                'label' => 'Growth 500',
                'amount_label' => '$500 tier',
                'eligible_count' => $activeInvestments->filter(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000)->count(),
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_growth_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_growth_max_rate'),
                'points_to_full_cap' => $profilePowerPointsToFullCap,
                'next_actions' => $profilePowerNextActionLabels,
            ],
            [
                'label' => 'Scale 1000+',
                'amount_label' => '$1000+ tier',
                'eligible_count' => $activeInvestments->filter(fn ($investment) => (float) $investment->amount >= 1000)->count(),
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_scale_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_scale_max_rate'),
                'points_to_full_cap' => $profilePowerPointsToFullCap,
                'next_actions' => $profilePowerNextActionLabels,
            ],
        ])->all();

        $displayTierName = $user->account_type === 'starter'
            ? ($user->investments->firstWhere('package.slug', MiningPlatform::FREE_STARTER_PACKAGE_SLUG)?->package?->name ?? 'Free Starter')
            : $level->name;

        return view('pages.general.profile', [
            'user' => $user,
            'level' => $level,
            'displayTierName' => $displayTierName,
            'starterPackage' => MiningPlatform::freeStarterPackage(),
            'starterProgress' => $starterProgress,
            'totalInvested' => $totalInvested,
            'activeInvestments' => $activeInvestments,
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
            'availableEarnings' => $availableEarnings,
            'pendingReferrals' => $pendingReferrals,
            'verifiedReferrals' => $verifiedReferrals,
            'registeredReferrals' => $registeredReferrals,
            'teamBonusRate' => $teamBonusRate,
            'profilePower' => $profilePower,
            'weeklyMomentum' => $weeklyMomentum,
            'monthlyMomentum' => $monthlyMomentum,
            'weeklyMomentumHistory' => $weeklyMomentumHistory,
            'profilePowerLeaderboard' => $leaderboard,
            'leaderboardPosition' => $leaderboardPosition === false ? null : $leaderboardPosition + 1,
            'recentRankCelebrations' => $recentRankCelebrations,
            'recentRewardCapCelebrations' => $recentRewardCapCelebrations,
            'recentHallOfFameWins' => $recentHallOfFameWins,
            'hallOfFameWinCounts' => $hallOfFameWinCounts,
            'profileRewardBoostSummary' => $profileRewardBoostSummary,
            'walletSummary' => $walletSummary,
            'profileInvestmentStatus' => $profileInvestmentStatus,
            'nextInvestmentUnlock' => $nextInvestmentUnlock,
            'profileInvestmentLabels' => $investmentAllocation->keys()->values()->all(),
            'profileInvestmentSeries' => $investmentAllocation->values()->all(),
            'profileFinanceLabels' => ['Invested', 'Monthly return', 'Wallet'],
            'profileFinanceSeries' => [
                round($totalInvested, 2),
                round($expectedMonthlyEarnings, 2),
                round($availableEarnings, 2),
            ],
            'profileReferralLabels' => ['Pending', 'Verified', 'Registered'],
            'profileReferralSeries' => [
                $pendingReferrals,
                $verifiedReferrals,
                $registeredReferrals,
            ],
        ]);
    })->name('dashboard.profile');

    Route::get('/dashboard/hall-of-fame', function () {
        MiningPlatform::ensureDefaults();

        $powerLeaders = MiningPlatform::competitionLeaderboard('power');
        $weeklyMovers = MiningPlatform::captureCompetitionSnapshot('weekly');
        $monthlyChampions = MiningPlatform::captureCompetitionSnapshot('monthly');
        $weeklyWinnerHistory = MiningPlatform::hallOfFameWinnerHistory('weekly');
        $monthlyChampionHistory = MiningPlatform::hallOfFameWinnerHistory('monthly');

        return view('pages.general.hall-of-fame', [
            'powerLeaders' => $powerLeaders,
            'weeklyMovers' => $weeklyMovers,
            'monthlyChampions' => $monthlyChampions,
            'weeklyWinnerHistory' => $weeklyWinnerHistory,
            'monthlyChampionHistory' => $monthlyChampionHistory,
        ]);
    })->name('dashboard.hall-of-fame');

    Route::get('/dashboard/investors/{user}', function (Request $request, User $user) {
        MiningPlatform::ensureDefaults();

        $viewer = $request->user();
        $user->load(['userLevel', 'investments.package', 'investments.miner']);
        $level = MiningPlatform::syncUserLevel($user);
        $user->load(['userLevel', 'investments.package', 'investments.miner']);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $totalInvested = (float) $activeInvestments->sum('amount');
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
        $teamBonusRate = MiningPlatform::teamBonusRate($user);
        $profilePower = MiningPlatform::profilePowerSummary($user);
        $recentHallOfFameWins = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => in_array(($notification->data['event_key'] ?? null), ['hall_of_fame_weekly_winner', 'hall_of_fame_monthly_winner'], true))
            ->take(4)
            ->values();
        $hallOfFameWinCounts = [
            'weekly' => HallOfFameSnapshot::query()->where('user_id', $user->id)->where('category', 'weekly')->where('rank_position', 1)->count(),
            'monthly' => HallOfFameSnapshot::query()->where('user_id', $user->id)->where('category', 'monthly')->where('rank_position', 1)->count(),
        ];
        $investmentAllocation = $activeInvestments
            ->groupBy(fn ($investment) => $investment->miner?->name ?? 'Unknown miner')
            ->map(fn ($investments) => round((float) $investments->sum('amount'), 2))
            ->sortDesc();
        $rewardCapUnlocksByTier = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
            ->keyBy(fn ($notification) => $notification->data['reward_cap_tier'] ?? '');
        $publicRewardCapSummary = collect([
            [
                'label' => 'Basic 100',
                'amount_label' => '$100 tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_basic_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_basic_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('basic'),
            ],
            [
                'label' => 'Growth 500',
                'amount_label' => '$500 tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_growth_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_growth_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('growth'),
            ],
            [
                'label' => 'Scale 1000+',
                'amount_label' => '$1000+ tier',
                'current_rate' => round((float) MiningPlatform::rewardSetting('profile_power_scale_max_rate') * ((float) $profilePower['score'] / 100), 4),
                'max_rate' => (float) MiningPlatform::rewardSetting('profile_power_scale_max_rate'),
                'unlock' => $rewardCapUnlocksByTier->get('scale'),
            ],
        ])->all();

        $displayTierName = $user->account_type === 'starter'
            ? ($user->investments->firstWhere('package.slug', MiningPlatform::FREE_STARTER_PACKAGE_SLUG)?->package?->name ?? 'Free Starter')
            : $level->name;

        return view('pages.general.investor-profile', [
            'viewer' => $viewer,
            'user' => $user,
            'level' => $level,
            'displayTierName' => $displayTierName,
            'activeInvestments' => $activeInvestments,
            'totalInvested' => $totalInvested,
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
            'teamBonusRate' => $teamBonusRate,
            'profilePower' => $profilePower,
            'publicRewardCapSummary' => $publicRewardCapSummary,
            'recentHallOfFameWins' => $recentHallOfFameWins,
            'hallOfFameWinCounts' => $hallOfFameWinCounts,
            'backTarget' => match (true) {
                $request->query('from') === 'shareholders' && $viewer->isAdmin() => route('dashboard.shareholders'),
                $request->query('from') === 'network-admin' && $viewer->isAdmin() => route('dashboard.network-admin'),
                $request->query('from') === 'network' => route('dashboard.network'),
                default => route('dashboard'),
            },
            'backLabel' => match (true) {
                $request->query('from') === 'shareholders' && $viewer->isAdmin() => 'Back to shareholders',
                $request->query('from') === 'network-admin' && $viewer->isAdmin() => 'Back to network admin',
                $request->query('from') === 'network' => 'Back to my network',
                default => 'Back to overview',
            },
            'investorAllocationLabels' => $investmentAllocation->keys()->values()->all(),
            'investorAllocationSeries' => $investmentAllocation->values()->all(),
        ]);
    })->name('dashboard.investors.show');
    Route::get('/dashboard/notifications', function (Request $request) {
        $filter = $request->query('filter', 'all');
        $allowedFilters = ['all', 'payout', 'reward', 'investment', 'network', 'milestone', 'digest'];

        if (! in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        $notifications = $request->user()->notifications()->latest()->get();
        $notificationFilters = [
            'all' => 'All',
            'payout' => 'Payouts',
            'reward' => 'Rewards',
            'investment' => 'Investments',
            'network' => 'Network',
            'milestone' => 'Milestones',
            'digest' => 'Digests',
        ];
        $filteredNotifications = $filter === 'all'
            ? $notifications
            : $notifications->filter(
                fn ($notification) => ($notification->data['category'] ?? 'payout') === $filter
            )->values();
        $notificationBreakdown = collect($notificationFilters)
            ->reject(fn ($label, $key) => $key === 'all')
            ->map(function ($label, $key) use ($notifications) {
                $items = $notifications->filter(fn ($notification) => ($notification->data['category'] ?? 'payout') === $key);

                return [
                    'label' => $label,
                    'count' => $items->count(),
                    'unread' => $items->whereNull('read_at')->count(),
                ];
            });

        return view('pages.general.notifications', [
            'notifications' => $filteredNotifications,
            'allNotificationsCount' => $notifications->count(),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'activeFilter' => $filter,
            'notificationFilters' => $notificationFilters,
            'notificationBreakdown' => $notificationBreakdown,
        ]);
    })->name('dashboard.notifications');

    Route::post('/dashboard/notifications/read-all', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('dashboard.notifications')->with('notifications_success', 'All notifications have been marked as read.');
    })->name('dashboard.notifications.read-all');

    Route::post('/dashboard/notifications/{notification}/read', function (Request $request, string $notification) {
        $dashboardNotification = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $dashboardNotification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'notification_id' => $dashboardNotification->id,
                'read_at' => optional($dashboardNotification->read_at)->toISOString(),
            ]);
        }

        return redirect()->route('dashboard.notifications')->with('notifications_success', 'Notification marked as read.');
    })->name('dashboard.notifications.read');

    Route::post('/dashboard/notifications/clear-read', function (Request $request) {
        $deleted = $request->user()->notifications()->whereNotNull('read_at')->delete();

        return redirect()->route('dashboard.notifications')->with('notifications_success', $deleted.' read notifications removed.');
    })->name('dashboard.notifications.clear-read');

    Route::post('/dashboard/notifications/clear-previews', function (Request $request) {
        $deleted = $request->user()->notifications()->get()
            ->filter(fn ($notification) => (bool) ($notification->data['is_preview'] ?? false))
            ->each(fn ($notification) => $notification->delete())
            ->count();

        return redirect()->route('dashboard.notifications')->with('notifications_success', $deleted.' preview notifications removed.');
    })->name('dashboard.notifications.clear-previews');

    Route::post('/dashboard/notifications/prune', function (Request $request) {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'filter' => ['required', 'string', 'in:all,payout,reward,investment,network,milestone,digest'],
            'older_than_days' => ['required', 'integer', 'min:1'],
        ]);

        $threshold = now()->subDays($validated['older_than_days']);
        $notifications = $request->user()->notifications()->where('created_at', '<', $threshold)->get();

        $deleted = $notifications
            ->filter(function ($notification) use ($validated) {
                if ($validated['filter'] === 'all') {
                    return true;
                }

                return ($notification->data['category'] ?? 'payout') === $validated['filter'];
            })
            ->each(fn ($notification) => $notification->delete())
            ->count();

        return redirect()->route('dashboard.notifications')->with('notifications_success', $deleted.' notifications removed by admin cleanup.');
    })->name('dashboard.notifications.prune');

    Route::get('/dashboard/notification-preferences', function () {
        $user = request()->user();

        return view('pages.general.notification-preferences', [
            'user' => $user,
            'notificationPreferences' => $user->notificationPreferences(),
            'digestSummary' => MiningPlatform::digestSummaryForUser($user),
            'recentDigests' => $user->notifications()
                ->where('data->category', 'digest')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    })->name('dashboard.notification-preferences');

    Route::post('/dashboard/notification-preferences', function (Request $request) {
        $request->validate([
            'payout_in_app' => ['nullable', 'boolean'],
            'payout_email' => ['nullable', 'boolean'],
            'reward_in_app' => ['nullable', 'boolean'],
            'reward_email' => ['nullable', 'boolean'],
            'investment_in_app' => ['nullable', 'boolean'],
            'investment_email' => ['nullable', 'boolean'],
            'network_in_app' => ['nullable', 'boolean'],
            'network_email' => ['nullable', 'boolean'],
            'milestone_in_app' => ['nullable', 'boolean'],
            'milestone_email' => ['nullable', 'boolean'],
            'digest_in_app' => ['nullable', 'boolean'],
            'digest_email' => ['nullable', 'boolean'],
            'digest_frequency' => ['required', 'string', 'in:daily,weekly'],
        ]);

        $categories = ['payout', 'reward', 'investment', 'network', 'milestone'];
        $preferences = [];

        foreach ($categories as $category) {
            $preferences[$category] = [
                'in_app' => $request->boolean($category.'_in_app'),
                'email' => $request->boolean($category.'_email'),
            ];
        }

        $preferences['digest'] = [
            'in_app' => $request->boolean('digest_in_app'),
            'email' => $request->boolean('digest_email'),
            'frequency' => $request->string('digest_frequency')->toString(),
        ];

        $request->user()->forceFill([
            'notification_preferences' => $preferences,
        ])->save();

        return redirect()->route('dashboard.notification-preferences')->with('preferences_success', 'Notification preferences updated successfully.');
    })->name('dashboard.notification-preferences.update');

    Route::post('/dashboard/notification-preferences/generate-digest', function (Request $request) {
        $user = $request->user();
        $summary = MiningPlatform::digestSummaryForUser($user, $user->digestFrequency());
        $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'user_manual', $user->id, $user->name));

        return redirect()->route('dashboard.notification-preferences')->with('preferences_success', ucfirst($summary['frequency']).' digest generated successfully.');
    })->name('dashboard.notification-preferences.generate-digest');

    Route::get('/dashboard/investments', function () {
        MiningPlatform::ensureDefaults();

        $source = request()->query('source', 'all');
        $sourceMap = [
            'all' => [
                'label' => 'All earnings',
                'sources' => ['mining_daily_share', 'mining_return', 'referral_registration', 'referral_subscription', 'team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'],
            ],
            'miner_daily_share' => [
                'label' => 'Miner daily share',
                'sources' => ['mining_daily_share'],
            ],
            'monthly_return' => [
                'label' => 'Monthly return',
                'sources' => ['mining_return'],
            ],
            'direct_referral' => [
                'label' => 'Direct referral rewards',
                'sources' => ['referral_registration', 'referral_subscription'],
            ],
            'mlm_network' => [
                'label' => 'MLM network rewards',
                'sources' => ['team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'],
            ],
        ];

        if (! array_key_exists($source, $sourceMap)) {
            $source = 'all';
        }

        $user = request()->user()->load(['investments.miner', 'investments.package', 'earnings.investment.package', 'earnings.investment.miner']);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $totalInvested = (float) $activeInvestments->sum('amount');
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
        $availableEarnings = (float) $user->earnings->where('status', 'available')->sum('amount');
        $earningsHistory = $user->earnings
            ->whereIn('source', $sourceMap['all']['sources'])
            ->sortByDesc(fn ($earning) => optional($earning->earned_on)?->timestamp ?? 0)
            ->values();
        $filteredEarnings = $source === 'all'
            ? $earningsHistory
            : $earningsHistory->whereIn('source', $sourceMap[$source]['sources'])->values();
        $earningsByDay = $filteredEarnings
            ->groupBy(fn ($earning) => $earning->earned_on?->format('Y-m-d'))
            ->map(fn ($earnings, $day) => [
                'day' => $day,
                'label' => optional(optional($earnings->first())->earned_on)->format('M d'),
                'total' => round((float) $earnings->sum('amount'), 2),
            ])
            ->sortBy('day')
            ->values();
        $earningsBreakdown = [
            'miner_daily_share' => [
                'label' => 'Miner daily share',
                'amount' => round((float) $earningsHistory->where('source', 'mining_daily_share')->sum('amount'), 2),
            ],
            'monthly_return' => [
                'label' => 'Monthly return',
                'amount' => round((float) $earningsHistory->where('source', 'mining_return')->sum('amount'), 2),
            ],
            'direct_referral' => [
                'label' => 'Direct referral rewards',
                'amount' => round((float) $earningsHistory->whereIn('source', ['referral_registration', 'referral_subscription'])->sum('amount'), 2),
            ],
            'mlm_network' => [
                'label' => 'MLM network rewards',
                'amount' => round((float) $earningsHistory->whereIn('source', ['team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'])->sum('amount'), 2),
            ],
        ];
        $investmentLivePerformance = $activeInvestments
            ->map(fn (UserInvestment $investment) => MiningPlatform::investmentLivePerformanceSummary($investment, 7))
            ->values();
        $profilePower = MiningPlatform::profilePowerSummary($user);
        $pointsToFullCap = max(100 - (int) $profilePower['score'], 0);
        $rewardActionLabels = collect($profilePower['recommended_actions'] ?? [])
            ->take(2)
            ->pluck('title')
            ->values()
            ->all();
        $rewardCapUnlocksByTier = $user->notifications()
            ->where('type', \App\Notifications\ActivityFeedNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
            ->keyBy(fn ($notification) => $notification->data['reward_cap_tier'] ?? '');
        $investmentRewardSummaries = $user->investments
            ->mapWithKeys(function (UserInvestment $investment) use ($pointsToFullCap, $rewardActionLabels, $rewardCapUnlocksByTier) {
                $currentBoostRate = MiningPlatform::investmentProfilePowerRewardRate($investment);
                $maxBoostRate = MiningPlatform::investmentProfilePowerRewardCap((float) $investment->amount);
                $tierKey = (float) $investment->amount >= 1000
                    ? 'scale'
                    : ((float) $investment->amount >= 500 ? 'growth' : 'basic');
                $capUnlockNotification = $rewardCapUnlocksByTier->get($tierKey);

                return [
                    $investment->id => [
                        'current_boost_rate' => $currentBoostRate,
                        'max_boost_rate' => $maxBoostRate,
                        'total_reward_rate' => MiningPlatform::investmentTotalRewardRate($investment),
                        'projected_reward_amount' => MiningPlatform::investmentProjectedRewardAmount($investment),
                        'points_to_full_cap' => $pointsToFullCap,
                        'next_actions' => $rewardActionLabels,
                        'tier_key' => $tierKey,
                        'cap_unlock_subject' => $capUnlockNotification->data['subject'] ?? null,
                        'cap_unlock_date' => $capUnlockNotification?->created_at,
                    ],
                ];
            })
            ->all();

        return view('pages.general.investments', [
            'user' => $user,
            'investments' => $user->investments,
            'activeInvestments' => $activeInvestments,
            'totalInvested' => $totalInvested,
            'totalSharesOwned' => (int) $activeInvestments->sum('shares_owned'),
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
            'availableEarnings' => $availableEarnings,
            'earningsHistory' => $filteredEarnings,
            'earningsByDay' => $earningsByDay,
            'totalFilteredEarnings' => round((float) $filteredEarnings->sum('amount'), 2),
            'earningsBreakdown' => $earningsBreakdown,
            'earningsSourceOptions' => $sourceMap,
            'activeSource' => $source,
            'investmentLivePerformance' => $investmentLivePerformance,
            'investmentRewardSummaries' => $investmentRewardSummaries,
        ]);
    })->name('dashboard.investments');

    Route::get('/dashboard/investment-orders', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $status = $request->query('status', 'all');
        $allowedStatuses = ['all', 'pending', 'approved', 'rejected', 'cancelled'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $user = $request->user();
        $orders = $user->investmentOrders()->with(['package', 'miner', 'approver'])->latest('submitted_at')->get();
        $filteredOrders = $status === 'all'
            ? $orders
            : $orders->where('status', $status)->values();

        return view('pages.general.investment-orders', [
            'orders' => $filteredOrders,
            'activeStatus' => $status,
            'orderCounts' => [
                'all' => $orders->count(),
                'pending' => $orders->where('status', 'pending')->count(),
                'approved' => $orders->where('status', 'approved')->count(),
                'rejected' => $orders->where('status', 'rejected')->count(),
                'cancelled' => $orders->where('status', 'cancelled')->count(),
            ],
        ]);
    })->name('dashboard.investment-orders');

    Route::post('/dashboard/investment-orders/{investmentOrder}/cancel', function (Request $request, InvestmentOrder $investmentOrder) {
        abort_unless($investmentOrder->user_id === $request->user()->id, 403);

        if ($investmentOrder->status !== 'pending') {
            return redirect()->route('dashboard.investment-orders')->withErrors([
                'cancel' => 'Only pending payment reviews can be cancelled.',
            ]);
        }

        $investmentOrder->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'admin_notes' => $investmentOrder->admin_notes ?: 'Cancelled by user before review.',
        ])->save();

        return redirect()->route('dashboard.investment-orders')->with('orders_success', 'Pending investment order cancelled successfully.');
    })->name('dashboard.investment-orders.cancel');

    Route::get('/dashboard/network', function () {
        MiningPlatform::ensureDefaults();

        $rewardCapBadgesForUser = function (User $targetUser): array {
            $rewardCapUnlocks = $targetUser->notifications()
                ->where('type', ActivityFeedNotification::class)
                ->latest()
                ->get()
                ->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'profile_power_reward_cap')
                ->pluck('data.reward_cap_tier')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $badges = [];

            if (in_array('basic', $rewardCapUnlocks, true)) {
                $badges[] = ['label' => '4% cap', 'class' => 'bg-primary-subtle text-primary'];
            }

            if (in_array('growth', $rewardCapUnlocks, true)) {
                $badges[] = ['label' => '6% cap', 'class' => 'bg-info-subtle text-info'];
            }

            if (in_array('scale', $rewardCapUnlocks, true)) {
                $badges[] = ['label' => '7% cap', 'class' => 'bg-dark text-white'];
            }

            return $badges;
        };

        $rewardFilter = request()->query('reward_filter', 'all');
        $rewardFilters = [
            'all' => 'All rewards',
            'direct' => 'Direct rewards',
            'mlm' => 'MLM rewards',
        ];
        if (! array_key_exists($rewardFilter, $rewardFilters)) {
            $rewardFilter = 'all';
        }

        $pipelineFilter = request()->query('pipeline_filter', 'all');
        $pipelineFilters = [
            'all' => 'All contacts',
            'verified' => 'Verified',
            'registered' => 'Registered',
            'active_investor' => 'Active investors',
            'pending' => 'Pending',
        ];
        if (! array_key_exists($pipelineFilter, $pipelineFilters)) {
            $pipelineFilter = 'all';
        }

        $user = request()->user()->load([
            'sponsor',
            'friendInvitations',
            'earnings',
            'referralEvents.relatedUser',
            'referralEvents.investment.package',
            'sponsoredUsers' => fn ($query) => $query->with([
                'userLevel',
                'friendInvitations',
                'investments.package',
                'investments.miner',
                'sponsoredUsers.userLevel',
                'sponsoredUsers.friendInvitations',
                'sponsoredUsers.investments.package',
                'sponsoredUsers.investments.miner',
                'sponsoredUsers.sponsoredUsers.investments',
            ])->orderBy('name'),
        ]);

        $friendInvitations = $user->friendInvitations->sortByDesc('created_at')->values();
        $invitedEmails = $friendInvitations->pluck('email')->filter()->unique()->values();
        $activeInvestorEmails = UserInvestment::query()
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query->whereIn('email', $invitedEmails))
            ->with('user:id,email')
            ->get()
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->values();

        $directTeam = $user->sponsoredUsers->values();
        $secondLevelTeam = $directTeam
            ->flatMap(fn ($member) => $member->sponsoredUsers)
            ->unique('id')
            ->values();

        $directTeamBranches = $directTeam->map(function ($member) use ($rewardCapBadgesForUser) {
            $activeInvestments = $member->investments->where('status', 'active')->where('amount', '>', 0);
            $branchSecondLevel = $member->sponsoredUsers->values();
            $branchSecondLevelActive = $branchSecondLevel->filter(fn ($downline) => $downline->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty());
            $weeklyMomentum = MiningPlatform::weeklyMomentumSummary($member);

            return [
                'member' => $member,
                'profile_power' => MiningPlatform::profilePowerSummary($member),
                'weekly_momentum' => $weeklyMomentum,
                'active_investments' => $activeInvestments,
                'active_capital' => (float) $activeInvestments->sum('amount'),
                'active_package' => $activeInvestments->sortByDesc('subscribed_at')->first()?->package?->name,
                'is_active_investor' => $activeInvestments->isNotEmpty(),
                'downline_count' => $branchSecondLevel->count(),
                'downline_active_count' => $branchSecondLevelActive->count(),
                'downline_capital' => (float) $branchSecondLevel->sum(fn ($downline) => $downline->investments->where('status', 'active')->where('amount', '>', 0)->sum('amount')),
                'reward_cap_badges' => $rewardCapBadgesForUser($member),
                'downline_members' => $branchSecondLevel->map(fn ($downline) => [
                    'member' => $downline,
                    'profile_power' => MiningPlatform::profilePowerSummary($downline),
                    'reward_cap_badges' => $rewardCapBadgesForUser($downline),
                ])->values(),
            ];
        })->values();
        $topTeamMember = $directTeamBranches
            ->sortByDesc(fn ($branch) => $branch['profile_power']['score'])
            ->first();
        $teamLeaderboard = $directTeamBranches
            ->sortByDesc(fn ($branch) => $branch['profile_power']['score'])
            ->take(5)
            ->values();
        $topMover = $directTeamBranches
            ->sortByDesc(fn ($branch) => $branch['weekly_momentum']['score'])
            ->first();
        $monthlyBranchChampion = $directTeamBranches
            ->map(function ($branch) {
                $branch['monthly_momentum'] = MiningPlatform::monthlyMomentumSummary($branch['member']);

                return $branch;
            })
            ->sortByDesc(fn ($branch) => $branch['monthly_momentum']['score'])
            ->first();

        $allReferralRewards = $user->earnings
            ->whereIn('source', ['referral_registration', 'referral_subscription', 'team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'])
            ->sortByDesc(fn ($reward) => optional($reward->earned_on)->timestamp ?? 0)
            ->values();
        $filteredReferralRewards = match ($rewardFilter) {
            'direct' => $allReferralRewards->whereIn('source', ['referral_registration', 'referral_subscription'])->values(),
            'mlm' => $allReferralRewards->whereIn('source', ['team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'])->values(),
            default => $allReferralRewards,
        };

        $filteredInvitations = $friendInvitations->filter(function ($friendInvitation) use ($pipelineFilter, $activeInvestorEmails) {
            return match ($pipelineFilter) {
                'verified' => ! is_null($friendInvitation->verified_at),
                'registered' => ! is_null($friendInvitation->registered_at),
                'active_investor' => $activeInvestorEmails->contains($friendInvitation->email),
                'pending' => is_null($friendInvitation->verified_at) && is_null($friendInvitation->registered_at) && ! $activeInvestorEmails->contains($friendInvitation->email),
                default => true,
            };
        })->values();

        $activeTeamInvestors = $directTeam->filter(fn ($member) => $member->investments->where('status', 'active')->isNotEmpty())->count();
        $teamCapital = (float) $directTeam->sum(fn ($member) => $member->investments->where('status', 'active')->sum('amount'));
        $teamBonusRate = MiningPlatform::teamBonusRate($user);

        return view('pages.general.network', [
            'user' => $user,
            'friendInvitations' => $filteredInvitations,
            'allFriendInvitationsCount' => $friendInvitations->count(),
            'directTeam' => $directTeam,
            'directTeamBranches' => $directTeamBranches,
            'secondLevelTeam' => $secondLevelTeam,
            'topTeamMember' => $topTeamMember,
            'teamLeaderboard' => $teamLeaderboard,
            'topMover' => $topMover,
            'monthlyBranchChampion' => $monthlyBranchChampion,
            'teamEvents' => $user->referralEvents->sortByDesc('created_at')->values(),
            'teamBonusRate' => $teamBonusRate,
            'activeTeamInvestors' => $activeTeamInvestors,
            'teamCapital' => $teamCapital,
            'invitedCount' => $friendInvitations->count(),
            'verifiedCount' => $friendInvitations->whereNotNull('verified_at')->count(),
            'registeredCount' => $friendInvitations->whereNotNull('registered_at')->count(),
            'subscribedCount' => $activeInvestorEmails->count(),
            'activeInvestorEmails' => $activeInvestorEmails,
            'referralRewards' => $filteredReferralRewards,
            'allReferralRewardsCount' => $allReferralRewards->count(),
            'referralRewardsTotal' => (float) $allReferralRewards->sum('amount'),
            'rewardBreakdown' => [
                'direct' => [
                    'label' => 'Direct rewards',
                    'count' => $allReferralRewards->whereIn('source', ['referral_registration', 'referral_subscription'])->count(),
                    'amount' => (float) $allReferralRewards->whereIn('source', ['referral_registration', 'referral_subscription'])->sum('amount'),
                ],
                'mlm' => [
                    'label' => 'MLM rewards',
                    'count' => $allReferralRewards->whereIn('source', ['team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'])->count(),
                    'amount' => (float) $allReferralRewards->whereIn('source', ['team_subscription_bonus', 'team_downline_bonus', 'team_level_3_bonus', 'team_level_4_bonus', 'team_level_5_bonus'])->sum('amount'),
                ],
            ],
            'rewardFilter' => $rewardFilter,
            'rewardFilters' => $rewardFilters,
            'pipelineFilter' => $pipelineFilter,
            'pipelineFilters' => $pipelineFilters,
            'pipelineBreakdown' => [
                'verified' => $friendInvitations->whereNotNull('verified_at')->count(),
                'registered' => $friendInvitations->whereNotNull('registered_at')->count(),
                'active_investor' => $activeInvestorEmails->count(),
                'pending' => $friendInvitations->filter(fn ($friendInvitation) => is_null($friendInvitation->verified_at) && is_null($friendInvitation->registered_at) && ! $activeInvestorEmails->contains($friendInvitation->email))->count(),
            ],
        ]);
    })->name('dashboard.network');

    Route::get('/dashboard/wallet', function () {
        MiningPlatform::ensureDefaults();

        $source = request()->query('source', 'all');
        $sourceMap = [
            'all' => [
                'label' => 'All earnings',
                'sources' => null,
            ],
            'miner_daily_share' => [
                'label' => 'Miner daily share',
                'sources' => ['mining_daily_share'],
            ],
            'monthly_return' => [
                'label' => 'Monthly return',
                'sources' => ['mining_return'],
            ],
            'direct_referral' => [
                'label' => 'Direct referral rewards',
                'sources' => ['referral_registration', 'referral_subscription'],
            ],
            'mlm_network' => [
                'label' => 'MLM network rewards',
                'sources' => [
                    'team_subscription_bonus',
                    'team_downline_bonus',
                    'team_level_3_bonus',
                    'team_level_4_bonus',
                    'team_level_5_bonus',
                ],
            ],
        ];

        if (! array_key_exists($source, $sourceMap)) {
            $source = 'all';
        }

        $user = request()->user()->load(['userLevel', 'earnings.investment.package', 'earnings.investment.miner', 'investments.package', 'payoutRequests']);
        MiningPlatform::syncMiningDailyShareUnlocks($user);
        $user->load(['earnings.investment.package', 'earnings.investment.miner']);
        $wallet = MiningPlatform::walletSummary($user);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
        $earnings = $user->earnings;
        $miningProfitCaps = $activeInvestments
            ->where('amount', '>', 0)
            ->map(fn ($investment) => [
                'investment_id' => $investment->id,
                'package_name' => $investment->package?->name ?? 'Package',
                'monthly_cap' => round(((float) $investment->amount) * (float) ($investment->package?->monthly_return_rate ?? $investment->monthly_return_rate), 2),
                'daily_cap' => MiningPlatform::investmentBaseDailyShareCap($investment),
            ])
            ->values();
        $packageWalletBreakdown = $activeInvestments
            ->where('amount', '>', 0)
            ->map(function ($investment) use ($earnings) {
                $unlockDate = $investment->subscribed_at?->copy()?->addDays(30);
                $investmentEarnings = $earnings->where('investment_id', $investment->id);

                return [
                    'investment_id' => $investment->id,
                    'package_name' => $investment->package?->name ?? 'Package',
                    'subscribed_at' => $investment->subscribed_at,
                    'unlock_date' => $unlockDate,
                    'is_unlocked' => $unlockDate ? now()->greaterThanOrEqualTo($unlockDate) : false,
                    'days_remaining' => $unlockDate && now()->lt($unlockDate) ? now()->diffInDays($unlockDate) : 0,
                    'available_amount' => round((float) $investmentEarnings->where('status', 'available')->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'locked_amount' => round((float) $investmentEarnings->whereIn('status', ['pending', 'payout_pending'])->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'projected_amount' => round((float) $investmentEarnings->where('source', 'projected_return')->sum('amount'), 2),
                    'daily_cap' => MiningPlatform::investmentBaseDailyShareCap($investment),
                    'monthly_cap' => round(((float) $investment->amount) * (float) ($investment->package?->monthly_return_rate ?? $investment->monthly_return_rate), 2),
                ];
            })
            ->values();

        $walletSourceBreakdown = [
            'miner_daily_share' => [
                'label' => 'Miner daily share',
                'amount' => round((float) $earnings->where('source', 'mining_daily_share')->sum('amount'), 2),
            ],
            'monthly_return' => [
                'label' => 'Monthly return',
                'amount' => round((float) $earnings->where('source', 'mining_return')->sum('amount'), 2),
            ],
            'direct_referral' => [
                'label' => 'Direct referral rewards',
                'amount' => round((float) $earnings->whereIn('source', ['referral_registration', 'referral_subscription'])->sum('amount'), 2),
            ],
            'mlm_network' => [
                'label' => 'MLM network rewards',
                'amount' => round((float) $earnings->whereIn('source', [
                    'team_subscription_bonus',
                    'team_downline_bonus',
                    'team_level_3_bonus',
                    'team_level_4_bonus',
                    'team_level_5_bonus',
                ])->sum('amount'), 2),
            ],
        ];

        $filteredEarnings = $source === 'all'
            ? $earnings
            : $earnings->whereIn('source', $sourceMap[$source]['sources'])->values();

        return view('pages.general.wallet', [
            'user' => $user,
            'wallet' => $wallet,
            'earnings' => $filteredEarnings,
            'walletSourceBreakdown' => $walletSourceBreakdown,
            'walletSourceOptions' => $sourceMap,
            'activeSource' => $source,
            'payoutRequests' => $user->payoutRequests,
            'activeInvestments' => $activeInvestments,
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
            'miningProfitCaps' => $miningProfitCaps,
            'packageWalletBreakdown' => $packageWalletBreakdown,
            'payoutMethods' => MiningPlatform::activePayoutMethods(),
            'defaultPayoutMethod' => collect(MiningPlatform::activePayoutMethods())->first(),
        ]);
    })->name('dashboard.wallet');

    Route::post('/dashboard/wallet/request', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load('earnings');
        $wallet = MiningPlatform::walletSummary($user);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(MiningPlatform::payoutMethodKeys())],
            'destination' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > $wallet['available']) {
            return back()->withErrors(['amount' => 'You can only withdraw from your available earnings. Your invested capital and share amount cannot be withdrawn.'])->withInput();
        }

        $quote = MiningPlatform::payoutQuote($validated['method'], (float) $validated['amount']);

        if ((float) $validated['amount'] < (float) ($quote['minimum_amount'] ?? 0)) {
            return back()->withErrors(['amount' => 'Requested amount is below the minimum for this payout method.'])->withInput();
        }

        if ((float) ($quote['net_amount'] ?? 0) <= 0) {
            return back()->withErrors(['amount' => 'Requested amount is too low after fees for this payout method.'])->withInput();
        }

        $payoutRequest = MiningPlatform::createPayoutRequest(
            $user,
            (float) $validated['amount'],
            $validated['method'],
            $validated['destination'],
            $validated['notes'] ?? null,
        );

        $user->notify(new PayoutStatusNotification($payoutRequest, 'submitted'));

        return redirect()->route('dashboard.wallet')->with('wallet_success', 'Your payout request has been submitted successfully.');
    })->middleware('throttle:5,1')->name('dashboard.wallet.request');

    Route::post('/dashboard/wallet/generate', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['investments']);
        $generated = MiningPlatform::generateMonthlyEarnings($user);

        return redirect()
            ->route('dashboard.wallet')
            ->with('wallet_success', $generated->count() > 0
                ? $generated->count().' monthly earning entries are now available in your wallet.'
                : 'No monthly return is available yet. Each paid package must complete a full 30-day cycle before the mining return unlocks.');
    })->name('dashboard.wallet.generate');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/miners', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.miners', [
                'miners' => Miner::with(['packages', 'investments'])->orderBy('name')->get(),
                'defaults' => MiningPlatform::platformSettings(),
            ]);
        })->name('dashboard.miners');

        Route::post('/dashboard/miners', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', 'unique:miners,slug'],
                'description' => ['nullable', 'string'],
                'total_shares' => ['required', 'integer', 'min:1'],
                'share_price' => ['required', 'numeric', 'min:1'],
                'daily_output_usd' => ['required', 'numeric', 'min:0'],
                'monthly_output_usd' => ['required', 'numeric', 'min:0'],
                'base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'status' => ['required', 'in:active,paused,maintenance'],
            ]);

            $miner = MiningPlatform::createMiner($validated);

            return redirect()->route('dashboard.miners')->with('miners_success', $miner->name.' has been created with starter packages and baseline logs.');
        })->name('dashboard.miners.store');
        Route::get('/dashboard/analytics', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with(['sponsor', 'userLevel', 'friendInvitations', 'sponsoredUsers.investments', 'investments.package', 'earnings'])->get();
            $packages = InvestmentPackage::with('investments')->orderBy('display_order')->get();
            $miner = MiningPlatform::resolveMiner($request->query('miner'));
            $miner->load([
                'performanceLogs' => fn ($query) => $query->orderBy('logged_on')->limit(7),
            ]);
            $miners = Miner::with(['investments.user', 'packages'])->orderBy('name')->get();
            $selectedMinerPerformanceLogs = $miner->performanceLogs;
            $selectedMinerSlug = $miner->slug;
            $treeDepth = max(2, min((int) $request->query('tree_depth', 3), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;
            $treeSearchResults = $treeSearch === ''
                ? collect()
                : $users
                    ->filter(fn (User $user) => str_contains(strtolower($user->name), strtolower($treeSearch)) || str_contains(strtolower($user->email), strtolower($treeSearch)))
                    ->take(8)
                    ->values();
            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $networkTreeSummary = MiningPlatform::referralTreeSummary($networkTree);
            $networkTreeChart = MiningPlatform::referralTreeChartPayload($networkTree, 'Analytics');

            $totalInvested = (float) UserInvestment::where('status', 'active')->sum('amount');

            $topInvestors = $users->sortByDesc(fn ($user) => $user->investments->where('status', 'active')->sum('amount'))->take(5)->values();
            $topReferrers = $users->sortByDesc(fn ($user) => $user->friendInvitations->whereNotNull('registered_at')->count())->take(5)->values();
            $mlmRewardBreakdown = collect([
                1 => 'team_subscription_bonus',
                2 => 'team_downline_bonus',
                3 => 'team_level_3_bonus',
                4 => 'team_level_4_bonus',
                5 => 'team_level_5_bonus',
            ])->map(function ($source, $level) {
                return [
                    'level' => $level,
                    'source' => $source,
                    'count' => Earning::where('source', $source)->count(),
                    'available_total' => (float) Earning::where('source', $source)->where('status', 'available')->sum('amount'),
                    'paid_total' => (float) Earning::where('source', $source)->where('status', 'paid')->sum('amount'),
                    'overall_total' => (float) Earning::where('source', $source)->sum('amount'),
                ];
            })->values();
            $rewardCapAnalyticsRows = $users
                ->map(function (User $trackedUser) {
                    $activeInvestments = $trackedUser->investments->where('status', 'active')->where('amount', '>', 0)->values();

                    if ($activeInvestments->isEmpty()) {
                        return null;
                    }

                    $profilePower = MiningPlatform::profilePowerSummary($trackedUser);
                    $basicUnlocked = $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500);
                    $growthUnlocked = $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000);
                    $scaleUnlocked = $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000);
                    $extraMonthlyLiability = round((float) $activeInvestments->sum(fn ($investment) => MiningPlatform::investmentProjectedRewardAmount($investment) - ((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate))), 2);

                    return [
                        'user' => $trackedUser,
                        'profile_power' => $profilePower,
                        'active_investments' => $activeInvestments,
                        'basic_unlocked' => $basicUnlocked,
                        'growth_unlocked' => $growthUnlocked,
                        'scale_unlocked' => $scaleUnlocked,
                        'extra_monthly_liability' => $extraMonthlyLiability,
                    ];
                })
                ->filter()
                ->values();
            $rewardCapAnalyticsSummary = [
                'basic_unlocked_users' => $rewardCapAnalyticsRows->where('basic_unlocked', true)->count(),
                'growth_unlocked_users' => $rewardCapAnalyticsRows->where('growth_unlocked', true)->count(),
                'scale_unlocked_users' => $rewardCapAnalyticsRows->where('scale_unlocked', true)->count(),
                'total_extra_monthly_liability' => round((float) $rewardCapAnalyticsRows->sum('extra_monthly_liability'), 2),
            ];
            $topRewardCapUsers = $rewardCapAnalyticsRows
                ->sortByDesc('extra_monthly_liability')
                ->take(5)
                ->values();

            $selectedMinerPerformanceSummary = [
                'total_revenue' => round((float) $selectedMinerPerformanceLogs->sum('revenue_usd'), 2),
                'total_net_profit' => round((float) $selectedMinerPerformanceLogs->sum('net_profit_usd'), 2),
                'average_uptime' => round((float) $selectedMinerPerformanceLogs->avg('uptime_percentage'), 2),
                'average_hashrate' => round((float) $selectedMinerPerformanceLogs->avg('hashrate_th'), 2),
                'average_per_share' => round((float) $selectedMinerPerformanceLogs->avg('revenue_per_share_usd'), 4),
                'automatic_runs' => (int) $selectedMinerPerformanceLogs->where('source', 'automatic')->count(),
            ];

            return view('pages.general.analytics', [
                'totalInvested' => $totalInvested,
                'totalSharesSold' => (int) UserInvestment::where('status', 'active')->sum('shares_owned'),
                'totalAvailableLiability' => (float) Earning::where('status', 'available')->sum('amount'),
                'totalPendingPayouts' => (float) PayoutRequest::whereIn('status', ['pending', 'approved'])->sum('amount'),
                'totalPaidOut' => (float) Earning::where('status', 'paid')->sum('amount'),
                'activeShareholders' => (int) User::where('account_type', 'shareholder')->count(),
                'packages' => $packages,
                'topInvestors' => $topInvestors,
                'topReferrers' => $topReferrers,
                'mlmRewardBreakdown' => $mlmRewardBreakdown,
                'rewardCapAnalyticsSummary' => $rewardCapAnalyticsSummary,
                'topRewardCapUsers' => $topRewardCapUsers,
                'miner' => $miner,
                'miners' => $miners,
                'selectedMinerSlug' => $selectedMinerSlug,
                'selectedMinerPerformanceLogs' => $selectedMinerPerformanceLogs,
                'selectedMinerPerformanceSummary' => $selectedMinerPerformanceSummary,
                'networkTree' => $networkTree,
                'networkTreeSummary' => $networkTreeSummary,
                'networkTreeChart' => $networkTreeChart,
                'treeDepth' => $treeDepth,
                'treeSearch' => $treeSearch,
                'treeSearchResults' => $treeSearchResults,
                'selectedTreeFocus' => $focusUser,
            ]);
        })->name('dashboard.analytics');

        Route::get('/dashboard/analytics/export', function () {
            MiningPlatform::ensureDefaults();

            $miner = MiningPlatform::resolveMiner(request()->query('miner'));
            $selectedMinerSlug = $miner->slug;
            $selectedMinerPerformanceLogs = $miner->performanceLogs()->orderBy('logged_on')->limit(7)->get();
            $rewardCapAnalyticsRows = User::with(['userLevel', 'friendInvitations', 'sponsoredUsers.investments', 'investments.package'])
                ->get()
                ->map(function (User $trackedUser) {
                    $activeInvestments = $trackedUser->investments->where('status', 'active')->where('amount', '>', 0)->values();

                    if ($activeInvestments->isEmpty()) {
                        return null;
                    }

                    $profilePower = MiningPlatform::profilePowerSummary($trackedUser);

                    return [
                        'user' => $trackedUser,
                        'profile_power' => $profilePower,
                        'basic_unlocked' => $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                        'growth_unlocked' => $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                        'scale_unlocked' => $profilePower['score'] >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                        'extra_monthly_liability' => round((float) $activeInvestments->sum(fn ($investment) => MiningPlatform::investmentProjectedRewardAmount($investment) - ((float) $investment->amount * ((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate + (float) $investment->team_bonus_rate))), 2),
                    ];
                })
                ->filter()
                ->values();
            $rewardCapAnalyticsSummary = [
                'basic_unlocked_users' => $rewardCapAnalyticsRows->where('basic_unlocked', true)->count(),
                'growth_unlocked_users' => $rewardCapAnalyticsRows->where('growth_unlocked', true)->count(),
                'scale_unlocked_users' => $rewardCapAnalyticsRows->where('scale_unlocked', true)->count(),
                'total_extra_monthly_liability' => round((float) $rewardCapAnalyticsRows->sum('extra_monthly_liability'), 2),
            ];
            $topRewardCapUsers = $rewardCapAnalyticsRows->sortByDesc('extra_monthly_liability')->take(5)->values();
            $mlmRewardBreakdown = collect([
                1 => 'team_subscription_bonus',
                2 => 'team_downline_bonus',
                3 => 'team_level_3_bonus',
                4 => 'team_level_4_bonus',
                5 => 'team_level_5_bonus',
            ])->map(function ($source, $level) {
                return [
                    'level' => $level,
                    'source' => $source,
                    'count' => Earning::where('source', $source)->count(),
                    'available_total' => (float) Earning::where('source', $source)->where('status', 'available')->sum('amount'),
                    'paid_total' => (float) Earning::where('source', $source)->where('status', 'paid')->sum('amount'),
                    'overall_total' => (float) Earning::where('source', $source)->sum('amount'),
                ];
            })->values();

            $filename = 'analytics-report-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($mlmRewardBreakdown, $miner, $selectedMinerSlug, $selectedMinerPerformanceLogs, $rewardCapAnalyticsSummary, $topRewardCapUsers) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Section', 'Label', 'Value']);
                fputcsv($handle, ['Summary', 'Selected miner slug', $selectedMinerSlug]);
                fputcsv($handle, ['Summary', 'Selected miner name', $miner->name]);
                fputcsv($handle, ['Summary', 'Total invested', number_format((float) UserInvestment::where('status', 'active')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Shares sold', (int) UserInvestment::where('status', 'active')->sum('shares_owned')]);
                fputcsv($handle, ['Summary', 'Available liability', number_format((float) Earning::where('status', 'available')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Pending payouts', number_format((float) PayoutRequest::whereIn('status', ['pending', 'approved'])->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Paid out', number_format((float) Earning::where('status', 'paid')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Active shareholders', (int) User::where('account_type', 'shareholder')->count()]);
                fputcsv($handle, ['Profile power reward', 'Users unlocked 4% cap', $rewardCapAnalyticsSummary['basic_unlocked_users']]);
                fputcsv($handle, ['Profile power reward', 'Users unlocked 6% cap', $rewardCapAnalyticsSummary['growth_unlocked_users']]);
                fputcsv($handle, ['Profile power reward', 'Users unlocked 7% cap', $rewardCapAnalyticsSummary['scale_unlocked_users']]);
                fputcsv($handle, ['Profile power reward', 'Estimated extra monthly liability', number_format($rewardCapAnalyticsSummary['total_extra_monthly_liability'], 2, '.', '')]);

                foreach ($topRewardCapUsers as $rewardCapUser) {
                    fputcsv($handle, ['Profile power reward leaders', $rewardCapUser['user']->email, number_format($rewardCapUser['extra_monthly_liability'], 2, '.', '')]);
                }

                foreach ($mlmRewardBreakdown as $rewardLevel) {
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' source', $rewardLevel['source']]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' entries', $rewardLevel['count']]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' available', number_format($rewardLevel['available_total'], 2, '.', '')]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' paid', number_format($rewardLevel['paid_total'], 2, '.', '')]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' total', number_format($rewardLevel['overall_total'], 2, '.', '')]);
                }

                fputcsv($handle, ['Selected miner daily performance', 'Miner', $miner->name]);
                foreach ($selectedMinerPerformanceLogs as $log) {
                    fputcsv($handle, ['Selected miner daily performance', $log->logged_on?->format('Y-m-d').' revenue', number_format((float) $log->revenue_usd, 2, '.', '')]);
                    fputcsv($handle, ['Selected miner daily performance', $log->logged_on?->format('Y-m-d').' costs', number_format((float) $log->electricity_cost_usd + (float) $log->maintenance_cost_usd, 2, '.', '')]);
                    fputcsv($handle, ['Selected miner daily performance', $log->logged_on?->format('Y-m-d').' net profit', number_format((float) $log->net_profit_usd, 2, '.', '')]);
                    fputcsv($handle, ['Selected miner daily performance', $log->logged_on?->format('Y-m-d').' per share', number_format((float) $log->revenue_per_share_usd, 4, '.', '')]);
                    fputcsv($handle, ['Selected miner daily performance', $log->logged_on?->format('Y-m-d').' uptime', number_format((float) $log->uptime_percentage, 2, '.', '').'%']);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.analytics.export');

        Route::get('/dashboard/analytics/tree-export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with(['sponsor', 'userLevel', 'friendInvitations', 'sponsoredUsers.investments', 'investments'])->get();
            $treeDepth = max(2, min((int) $request->query('tree_depth', 3), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;

            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $rows = MiningPlatform::flattenedReferralTree($networkTree);

            $filename = 'analytics-tree-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($rows, $focusUser, $treeDepth, $treeSearch) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filter', 'Value']);
                fputcsv($handle, ['Tree depth', $treeDepth]);
                fputcsv($handle, ['Search term', $treeSearch === '' ? 'All' : $treeSearch]);
                fputcsv($handle, ['Focused branch', $focusUser?->email ?? 'All visible roots']);
                fputcsv($handle, []);
                fputcsv($handle, ['Name', 'Email', 'Sponsor', 'Level', 'Depth', 'Health', 'Priority', 'Power', 'Direct team', 'Active direct investors', 'Active capital', 'Branch capital', 'Visible descendants', 'Branch investors', 'Verified invites']);

                foreach ($rows as $node) {
                    fputcsv($handle, [
                        $node['user']->name,
                        $node['user']->email,
                        $node['sponsor_name'],
                        $node['level_name'],
                        $node['depth'],
                        $node['situation']['health'],
                        $node['situation']['priority'],
                        $node['power_summary']['score'].'/100',
                        $node['direct_team'],
                        $node['active_direct_investors'],
                        number_format((float) $node['active_capital'], 2, '.', ''),
                        number_format((float) $node['branch_active_capital'], 2, '.', ''),
                        $node['visible_descendants'],
                        $node['branch_active_investors'],
                        $node['verified_invites'],
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.analytics.tree-export');

        Route::get('/dashboard/analytics/tree-print', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with(['sponsor', 'userLevel', 'friendInvitations', 'sponsoredUsers.investments', 'investments'])->get();
            $treeDepth = max(2, min((int) $request->query('tree_depth', 3), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;

            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $rows = MiningPlatform::flattenedReferralTree($networkTree);

            return view('pages.general.network-branch-print', [
                'pageTitle' => 'Analytics Branch View',
                'summary' => MiningPlatform::referralTreeSummary($networkTree),
                'rows' => $rows,
                'focusUser' => $focusUser,
                'treeDepth' => $treeDepth,
                'treeSearch' => $treeSearch,
                'branchCapital' => (float) $rows->sum('active_capital'),
                'branchInvestorCount' => (int) $rows->filter(fn (array $node) => (float) $node['active_capital'] > 0)->count(),
            ]);
        })->name('dashboard.analytics.tree-print');

        Route::get('/dashboard/digests', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $segment = $request->query('segment', 'all');
            $allowedSegments = ['all', 'email_enabled', 'no_recent_activity', 'daily_only', 'weekly_only'];

            if (! in_array($segment, $allowedSegments, true)) {
                $segment = 'all';
            }


            $digestRows = User::query()
                ->whereNotNull('email_verified_at')
                ->orderBy('name')
                ->get()
                ->map(function (User $user) {
                    $preferences = $user->notificationPreferences();
                    $frequency = $user->digestFrequency();
                    $digestSummary = MiningPlatform::digestSummaryForUser($user, $frequency);
                    $recentDigestCount = $user->notifications()
                        ->where('data->category', 'digest')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count();

                    return [
                        'user' => $user,
                        'preferences' => $preferences,
                        'frequency' => $frequency,
                        'digest_summary' => $digestSummary,
                        'recent_digest_count' => $recentDigestCount,
                        'is_inactive' => $digestSummary['total'] === 0 && $recentDigestCount === 0,
                    ];
                })
                ->values();

            $filteredDigestRows = $digestRows
                ->filter(function (array $row) use ($segment) {
                    return match ($segment) {
                        'email_enabled' => (bool) ($row['preferences']['digest']['email'] ?? false),
                        'no_recent_activity' => $row['is_inactive'],
                        'daily_only' => $row['frequency'] === 'daily',
                        'weekly_only' => $row['frequency'] === 'weekly',
                        default => true,
                    };
                })
                ->values();


            return view('pages.general.digests', [
                'digestRows' => $filteredDigestRows,
                'totalDigestRowsCount' => $digestRows->count(),
                'dailyUsersCount' => $digestRows->where('frequency', 'daily')->count(),
                'weeklyUsersCount' => $digestRows->where('frequency', 'weekly')->count(),
                'emailEnabledCount' => $digestRows->filter(fn (array $row) => (bool) ($row['preferences']['digest']['email'] ?? false))->count(),
                'inactiveCount' => $digestRows->where('is_inactive', true)->count(),
                'activeSegment' => $segment,
                'segmentOptions' => [
                    'all' => 'All segments',
                    'email_enabled' => 'Email enabled',
                    'no_recent_activity' => 'No recent activity',
                    'daily_only' => 'Daily only',
                    'weekly_only' => 'Weekly only',
                ],
                'investmentPaymentMethodReviews' => collect([
                    'btc_transfer' => MiningPlatform::platformSetting('payment_btc_transfer_admin_review_note'),
                    'usdt_transfer' => MiningPlatform::platformSetting('payment_usdt_transfer_admin_review_note'),
                    'bank_transfer' => MiningPlatform::platformSetting('payment_bank_transfer_admin_review_note'),
                ]),
            ]);
        })->name('dashboard.digests');

        Route::post('/dashboard/digests/{user}/send', function (Request $request, User $user) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'frequency' => ['required', 'string', 'in:daily,weekly'],
            ]);

            abort_unless($user->hasVerifiedEmail(), 422, 'Digest can only be sent to verified users.');

            $summary = MiningPlatform::digestSummaryForUser($user, $validated['frequency']);
            $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'admin_manual', $request->user()->id, $request->user()->adminLabel()));

            if ($summary['frequency'] === 'daily') {
                $user->forceFill(['last_daily_digest_sent_at' => now()])->save();
            } else {
                $user->forceFill(['last_weekly_digest_sent_at' => now()])->save();
            }

            return redirect()
                ->route('dashboard.digests')
                ->with('digests_success', ucfirst($summary['frequency']).' digest sent to '. $user->email . '.');
        })->name('dashboard.digests.send');

        Route::post('/dashboard/digests/send-bulk', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'frequency' => ['required', 'string', 'in:daily,weekly'],
                'scope' => ['required', 'string', 'in:matching,all_verified'],
                'segment' => ['required', 'string', 'in:all,email_enabled,no_recent_activity,daily_only,weekly_only'],
            ]);

            $users = User::query()
                ->whereNotNull('email_verified_at')
                ->orderBy('name')
                ->get()
                ->filter(function (User $user) use ($validated) {
                    if ($validated['scope'] !== 'all_verified' && $user->digestFrequency() !== $validated['frequency']) {
                        return false;
                    }

                    $preferences = $user->notificationPreferences();
                    $digestSummary = MiningPlatform::digestSummaryForUser($user, $validated['frequency']);
                    $recentDigestCount = $user->notifications()
                        ->where('data->category', 'digest')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count();
                    $isInactive = $digestSummary['total'] === 0 && $recentDigestCount === 0;

                    return match ($validated['segment']) {
                        'email_enabled' => (bool) ($preferences['digest']['email'] ?? false),
                        'no_recent_activity' => $isInactive,
                        'daily_only' => $user->digestFrequency() === 'daily',
                        'weekly_only' => $user->digestFrequency() === 'weekly',
                        default => true,
                    };
                })
                ->values();

            foreach ($users as $user) {
                $summary = MiningPlatform::digestSummaryForUser($user, $validated['frequency']);
                $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'admin_bulk', $request->user()->id, $request->user()->adminLabel()));

                if ($summary['frequency'] === 'daily') {
                    $user->forceFill(['last_daily_digest_sent_at' => now()])->save();
                } else {
                    $user->forceFill(['last_weekly_digest_sent_at' => now()])->save();
                }
            }

            return redirect()
                ->route('dashboard.digests')
                ->with('digests_success', ucfirst($validated['frequency']).' digests sent to '. $users->count() . ' verified users.');
        })->name('dashboard.digests.bulk-send');

        Route::get('/dashboard/digests/history', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $source = $request->query('source', 'all');
            $frequency = $request->query('frequency', 'all');
            $allowedSources = ['all', 'admin_manual', 'admin_bulk', 'user_manual', 'scheduled'];
            $allowedFrequencies = ['all', 'daily', 'weekly'];

            if (! in_array($source, $allowedSources, true)) {
                $source = 'all';
            }

            if (! in_array($frequency, $allowedFrequencies, true)) {
                $frequency = 'all';
            }

            $history = User::query()
                ->whereNotNull('email_verified_at')
                ->with(['notifications' => fn ($query) => $query->where('data->category', 'digest')->latest()->limit(50)])
                ->orderBy('name')
                ->get()
                ->flatMap(function (User $user) {
                    return $user->notifications->map(function ($notification) use ($user) {
                        return [
                            'user' => $user,
                            'notification' => $notification,
                            'data' => $notification->data,
                        ];
                    });
                })
                ->filter(function (array $entry) use ($source, $frequency) {
                    $entrySource = $entry['data']['digest_source'] ?? 'system';
                    $entryFrequency = $entry['data']['digest_frequency'] ?? 'weekly';

                    if ($source !== 'all' && $entrySource !== $source) {
                        return false;
                    }

                    if ($frequency !== 'all' && $entryFrequency !== $frequency) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc(fn (array $entry) => optional($entry['notification']->created_at)->timestamp ?? 0)
                ->take(100)
                ->values();

            $sourceBreakdown = collect(['admin_manual', 'admin_bulk', 'user_manual', 'scheduled'])
                ->mapWithKeys(fn (string $key) => [$key => $history->where('data.digest_source', $key)->count()]);
            $frequencyBreakdown = collect(['daily', 'weekly'])
                ->mapWithKeys(fn (string $key) => [$key => $history->where('data.digest_frequency', $key)->count()]);
            $topRecipients = $history
                ->groupBy(fn (array $entry) => $entry['user']->email)
                ->map(function ($entries) {
                    $first = $entries->first();

                    return [
                        'name' => $first['user']->name,
                        'email' => $first['user']->email,
                        'count' => $entries->count(),
                    ];
                })
                ->sortByDesc('count')
                ->take(5)
                ->values();
            $totalUpdatesDelivered = (int) $history->sum(fn (array $entry) => (int) ($entry['data']['amount'] ?? 0));

            return view('pages.general.digest-history', [
                'history' => $history,
                'activeSource' => $source,
                'activeFrequency' => $frequency,
                'sourceOptions' => [
                    'all' => 'All sources',
                    'admin_manual' => 'Admin manual',
                    'admin_bulk' => 'Admin bulk',
                    'user_manual' => 'User manual',
                    'scheduled' => 'Scheduled',
                ],
                'frequencyOptions' => [
                    'all' => 'All frequencies',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                ],
                'sourceBreakdown' => $sourceBreakdown,
                'frequencyBreakdown' => $frequencyBreakdown,
                'topRecipients' => $topRecipients,
                'totalUpdatesDelivered' => $totalUpdatesDelivered,
            ]);
        })->name('dashboard.digests.history');

        Route::get('/dashboard/digests/history/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $source = $request->query('source', 'all');
            $frequency = $request->query('frequency', 'all');
            $allowedSources = ['all', 'admin_manual', 'admin_bulk', 'user_manual', 'scheduled'];
            $allowedFrequencies = ['all', 'daily', 'weekly'];

            if (! in_array($source, $allowedSources, true)) {
                $source = 'all';
            }

            if (! in_array($frequency, $allowedFrequencies, true)) {
                $frequency = 'all';
            }

            $history = User::query()
                ->whereNotNull('email_verified_at')
                ->with(['notifications' => fn ($query) => $query->where('data->category', 'digest')->latest()->limit(50)])
                ->orderBy('name')
                ->get()
                ->flatMap(function (User $user) {
                    return $user->notifications->map(function ($notification) use ($user) {
                        return [
                            'user' => $user,
                            'notification' => $notification,
                            'data' => $notification->data,
                        ];
                    });
                })
                ->filter(function (array $entry) use ($source, $frequency) {
                    $entrySource = $entry['data']['digest_source'] ?? 'system';
                    $entryFrequency = $entry['data']['digest_frequency'] ?? 'weekly';

                    if ($source !== 'all' && $entrySource !== $source) {
                        return false;
                    }

                    if ($frequency !== 'all' && $entryFrequency !== $frequency) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc(fn (array $entry) => optional($entry['notification']->created_at)->timestamp ?? 0)
                ->take(100)
                ->values();

            $filename = 'digest-history-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($history) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Recipient Name', 'Recipient Email', 'Frequency', 'Source', 'Triggered By', 'Period', 'Total Updates', 'Created At']);

                foreach ($history as $entry) {
                    fputcsv($handle, [
                        $entry['user']->name,
                        $entry['user']->email,
                        $entry['data']['digest_frequency'] ?? 'weekly',
                        $entry['data']['digest_source'] ?? 'system',
                        (isset($entry['data']['triggered_by_id']) && $entry['data']['triggered_by_id'])
                            ? 'Admin #'.$entry['data']['triggered_by_id']
                            : ($entry['data']['triggered_by_name'] ?? 'System'),
                        $entry['data']['period_label'] ?? 'Last activity window',
                        $entry['data']['amount'] ?? 0,
                        optional($entry['notification']->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.digests.history.export');
        Route::get('/dashboard/network-admin', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with([
                'sponsor',
                'userLevel',
                'friendInvitations',
                'investments.package',
                'investments.miner',
                'sponsoredUsers.investments',
            ])->orderBy('name')->get();
            $treeDepth = max(2, min((int) $request->query('tree_depth', 5), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;
            $treeSearchResults = $treeSearch === ''
                ? collect()
                : $users
                    ->filter(fn (User $user) => str_contains(strtolower($user->name), strtolower($treeSearch)) || str_contains(strtolower($user->email), strtolower($treeSearch)))
                    ->take(8)
                    ->values();
            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $networkTreeSummary = MiningPlatform::referralTreeSummary($networkTree);
            $networkTreeChart = MiningPlatform::referralTreeChartPayload($networkTree, 'Network Admin');

            return view('pages.general.network-admin', [
                'users' => $users,
                'networkTree' => $networkTree,
                'networkTreeSummary' => $networkTreeSummary,
                'networkTreeChart' => $networkTreeChart,
                'treeDepth' => $treeDepth,
                'treeSearch' => $treeSearch,
                'treeSearchResults' => $treeSearchResults,
                'selectedTreeFocus' => $focusUser,
                'events' => ReferralEvent::with(['sponsor', 'relatedUser', 'investment.package'])
                    ->latest()
                    ->limit(25)
                    ->get(),
            ]);
        })->name('dashboard.network-admin');

        Route::get('/dashboard/network-admin/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with([
                'sponsor',
                'userLevel',
                'friendInvitations',
                'investments.package',
                'investments.miner',
                'sponsoredUsers.investments',
            ])->orderBy('name')->get();

            $treeDepth = max(2, min((int) $request->query('tree_depth', 5), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;

            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $rows = MiningPlatform::flattenedReferralTree($networkTree);

            $filename = 'network-admin-tree-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($rows, $focusUser, $treeDepth, $treeSearch) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filter', 'Value']);
                fputcsv($handle, ['Tree depth', $treeDepth]);
                fputcsv($handle, ['Search term', $treeSearch === '' ? 'All' : $treeSearch]);
                fputcsv($handle, ['Focused branch', $focusUser?->email ?? 'All visible roots']);
                fputcsv($handle, []);
                fputcsv($handle, ['Name', 'Email', 'Sponsor', 'Level', 'Depth', 'Health', 'Priority', 'Power', 'Direct team', 'Active direct investors', 'Active capital', 'Branch capital', 'Visible descendants', 'Branch investors', 'Verified invites']);

                foreach ($rows as $node) {
                    fputcsv($handle, [
                        $node['user']->name,
                        $node['user']->email,
                        $node['sponsor_name'],
                        $node['level_name'],
                        $node['depth'],
                        $node['situation']['health'],
                        $node['situation']['priority'],
                        $node['power_summary']['score'].'/100',
                        $node['direct_team'],
                        $node['active_direct_investors'],
                        number_format((float) $node['active_capital'], 2, '.', ''),
                        number_format((float) $node['branch_active_capital'], 2, '.', ''),
                        $node['visible_descendants'],
                        $node['branch_active_investors'],
                        $node['verified_invites'],
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.network-admin.export');

        Route::get('/dashboard/network-admin/print', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $users = User::with([
                'sponsor',
                'userLevel',
                'friendInvitations',
                'investments.package',
                'investments.miner',
                'sponsoredUsers.investments',
            ])->orderBy('name')->get();

            $treeDepth = max(2, min((int) $request->query('tree_depth', 5), 6));
            $treeSearch = trim((string) $request->query('tree_search', ''));
            $focusUser = $request->filled('tree_focus')
                ? $users->firstWhere('id', (int) $request->query('tree_focus'))
                : null;

            $networkTree = $focusUser
                ? MiningPlatform::referralSubtree($users, $focusUser, $treeDepth)
                : MiningPlatform::referralTree($users, $treeDepth);
            $rows = MiningPlatform::flattenedReferralTree($networkTree);

            return view('pages.general.network-branch-print', [
                'pageTitle' => 'Network Admin Branch View',
                'summary' => MiningPlatform::referralTreeSummary($networkTree),
                'rows' => $rows,
                'focusUser' => $focusUser,
                'treeDepth' => $treeDepth,
                'treeSearch' => $treeSearch,
                'branchCapital' => (float) $rows->sum('active_capital'),
                'branchInvestorCount' => (int) $rows->filter(fn (array $node) => (float) $node['active_capital'] > 0)->count(),
            ]);
        })->name('dashboard.network-admin.print');

        Route::get('/dashboard/shareholders', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $minerSlug = $request->query('miner');
            $status = $request->query('status');
            $packageSlug = $request->query('package');
            $search = trim((string) $request->query('search', ''));
            $rewardCap = (string) $request->query('reward_cap', 'all');
            $allowedRewardCaps = ['all', 'basic', 'growth', 'scale'];

            if (! in_array($rewardCap, $allowedRewardCaps, true)) {
                $rewardCap = 'all';
            }

            $rewardCapMeta = [
                'basic' => ['label' => 'Basic 100', 'short' => '4% cap', 'max_rate' => 0.04],
                'growth' => ['label' => 'Growth 500', 'short' => '6% cap', 'max_rate' => 0.06],
                'scale' => ['label' => 'Scale 1000+', 'short' => '7% cap', 'max_rate' => 0.07],
            ];
            $rewardCapCache = [];
            $resolveRewardCaps = function (?User $user) use (&$rewardCapCache, $rewardCapMeta) {
                if (! $user) {
                    return collect($rewardCapMeta)->map(fn (array $meta, string $key) => [
                        'key' => $key,
                        'label' => $meta['label'],
                        'short' => $meta['short'],
                        'max_rate' => $meta['max_rate'],
                        'unlocked' => false,
                    ])->all();
                }

                if (array_key_exists($user->id, $rewardCapCache)) {
                    return $rewardCapCache[$user->id];
                }

                $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
                $score = (int) (MiningPlatform::profilePowerSummary($user)['score'] ?? 0);
                $activeInvestments = $user->investments->where('status', 'active');

                return $rewardCapCache[$user->id] = [
                    'basic' => [
                        'key' => 'basic',
                        'label' => $rewardCapMeta['basic']['label'],
                        'short' => $rewardCapMeta['basic']['short'],
                        'max_rate' => $rewardCapMeta['basic']['max_rate'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                    ],
                    'growth' => [
                        'key' => 'growth',
                        'label' => $rewardCapMeta['growth']['label'],
                        'short' => $rewardCapMeta['growth']['short'],
                        'max_rate' => $rewardCapMeta['growth']['max_rate'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                    ],
                    'scale' => [
                        'key' => 'scale',
                        'label' => $rewardCapMeta['scale']['label'],
                        'short' => $rewardCapMeta['scale']['short'],
                        'max_rate' => $rewardCapMeta['scale']['max_rate'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                    ],
                ];
            };
            $investmentTier = function (UserInvestment $investment): ?string {
                return match (true) {
                    (float) $investment->amount >= 1000 => 'scale',
                    (float) $investment->amount >= 500 => 'growth',
                    (float) $investment->amount > 0 => 'basic',
                    default => null,
                };
            };
            $matchesRewardCap = function (UserInvestment $investment, string $selectedCap) use ($resolveRewardCaps, $investmentTier): bool {
                if ($selectedCap === 'all') {
                    return true;
                }

                $tier = $investmentTier($investment);

                return $tier === $selectedCap
                    && (($resolveRewardCaps($investment->user)[$selectedCap]['unlocked'] ?? false) === true);
            };

            $investmentsQuery = UserInvestment::with(['user', 'miner', 'package'])
                ->when($minerSlug, fn ($query) => $query->whereHas('miner', fn ($minerQuery) => $minerQuery->where('slug', $minerSlug)))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($packageSlug, fn ($query) => $query->whereHas('package', fn ($packageQuery) => $packageQuery->where('slug', $packageSlug)))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->whereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%"))
                            ->orWhereHas('miner', fn ($minerQuery) => $minerQuery->where('name', 'like', "%$search%"))
                            ->orWhereHas('package', fn ($packageQuery) => $packageQuery->where('name', 'like', "%$search%"));
                    });
                });

            $baseInvestments = $investmentsQuery->latest('subscribed_at')->get();
            $investments = $baseInvestments
                ->filter(fn (UserInvestment $investment) => $matchesRewardCap($investment, $rewardCap))
                ->values();
            $allInvestments = UserInvestment::with(['user', 'miner', 'package'])->get();
            $statusBreakdown = collect(['active', 'pending', 'closed'])->mapWithKeys(function ($statusKey) use ($allInvestments, $minerSlug, $packageSlug, $search, $rewardCap, $matchesRewardCap) {
                $items = $allInvestments
                    ->when($minerSlug, fn ($collection) => $collection->filter(fn ($investment) => $investment->miner?->slug === $minerSlug))
                    ->when($packageSlug, fn ($collection) => $collection->filter(fn ($investment) => $investment->package?->slug === $packageSlug))
                    ->when($search !== '', function ($collection) use ($search) {
                        return $collection->filter(function ($investment) use ($search) {
                            return str_contains(strtolower((string) $investment->user?->name), strtolower($search))
                                || str_contains(strtolower((string) $investment->user?->email), strtolower($search))
                                || str_contains(strtolower((string) $investment->miner?->name), strtolower($search))
                                || str_contains(strtolower((string) $investment->package?->name), strtolower($search));
                        });
                    })
                    ->when($rewardCap !== 'all', fn ($collection) => $collection->filter(fn ($investment) => $matchesRewardCap($investment, $rewardCap)))
                    ->where('status', $statusKey);

                return [$statusKey => $items->count()];
            });
            $minerBreakdown = $allInvestments
                ->when($packageSlug, fn ($collection) => $collection->filter(fn ($investment) => $investment->package?->slug === $packageSlug))
                ->when($status, fn ($collection) => $collection->where('status', $status))
                ->when($search !== '', function ($collection) use ($search) {
                    return $collection->filter(function ($investment) use ($search) {
                        return str_contains(strtolower((string) $investment->user?->name), strtolower($search))
                            || str_contains(strtolower((string) $investment->user?->email), strtolower($search))
                            || str_contains(strtolower((string) $investment->miner?->name), strtolower($search))
                            || str_contains(strtolower((string) $investment->package?->name), strtolower($search));
                    });
                })
                ->when($rewardCap !== 'all', fn ($collection) => $collection->filter(fn ($investment) => $matchesRewardCap($investment, $rewardCap)))
                ->groupBy(fn ($investment) => $investment->miner?->slug ?? 'unknown')
                ->map(fn ($items) => [
                    'name' => $items->first()?->miner?->name ?? 'Unknown miner',
                    'count' => $items->count(),
                ]);
            $rewardCapBreakdown = collect($rewardCapMeta)->map(function (array $meta, string $key) use ($baseInvestments, $matchesRewardCap) {
                return [
                    'label' => $meta['label'],
                    'short' => $meta['short'],
                    'count' => $baseInvestments->filter(fn (UserInvestment $investment) => $matchesRewardCap($investment, $key))->count(),
                ];
            });
            $investmentRewardCaps = $investments->mapWithKeys(function (UserInvestment $investment) use ($resolveRewardCaps) {
                $badges = collect($resolveRewardCaps($investment->user))
                    ->filter(fn (array $cap) => $cap['unlocked'])
                    ->values()
                    ->all();

                return [$investment->id => $badges];
            });

            return view('pages.general.shareholders', [
                'miners' => Miner::orderBy('name')->get(),
                'packages' => \App\Models\InvestmentPackage::orderBy('price')->get(),
                'investments' => $investments,
                'selectedMiner' => $minerSlug,
                'selectedStatus' => $status,
                'selectedPackage' => $packageSlug,
                'search' => $search,
                'selectedRewardCap' => $rewardCap,
                'statusBreakdown' => $statusBreakdown,
                'minerBreakdown' => $minerBreakdown,
                'rewardCapBreakdown' => $rewardCapBreakdown,
                'investmentRewardCaps' => $investmentRewardCaps,
            ]);
        })->name('dashboard.shareholders');

        Route::get('/dashboard/shareholders/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $minerSlug = $request->query('miner');
            $status = $request->query('status');
            $packageSlug = $request->query('package');
            $search = trim((string) $request->query('search', ''));
            $rewardCap = (string) $request->query('reward_cap', 'all');
            $allowedRewardCaps = ['all', 'basic', 'growth', 'scale'];

            if (! in_array($rewardCap, $allowedRewardCaps, true)) {
                $rewardCap = 'all';
            }

            $rewardCapCache = [];
            $resolveRewardCaps = function (?User $user) use (&$rewardCapCache) {
                if (! $user) {
                    return ['basic' => false, 'growth' => false, 'scale' => false];
                }

                if (array_key_exists($user->id, $rewardCapCache)) {
                    return $rewardCapCache[$user->id];
                }

                $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
                $score = (int) (MiningPlatform::profilePowerSummary($user)['score'] ?? 0);
                $activeInvestments = $user->investments->where('status', 'active');

                return $rewardCapCache[$user->id] = [
                    'basic' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                    'growth' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                    'scale' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                ];
            };
            $matchesRewardCap = function (UserInvestment $investment, string $selectedCap) use ($resolveRewardCaps): bool {
                if ($selectedCap === 'all') {
                    return true;
                }

                $tier = match (true) {
                    (float) $investment->amount >= 1000 => 'scale',
                    (float) $investment->amount >= 500 => 'growth',
                    (float) $investment->amount > 0 => 'basic',
                    default => null,
                };

                return $tier === $selectedCap
                    && (($resolveRewardCaps($investment->user)[$selectedCap] ?? false) === true);
            };

            $investments = UserInvestment::with(['user', 'miner', 'package'])
                ->when($minerSlug, fn ($query) => $query->whereHas('miner', fn ($minerQuery) => $minerQuery->where('slug', $minerSlug)))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($packageSlug, fn ($query) => $query->whereHas('package', fn ($packageQuery) => $packageQuery->where('slug', $packageSlug)))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->whereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%"))
                            ->orWhereHas('miner', fn ($minerQuery) => $minerQuery->where('name', 'like', "%$search%"))
                            ->orWhereHas('package', fn ($packageQuery) => $packageQuery->where('name', 'like', "%$search%"));
                    });
                })
                ->latest('subscribed_at')
                ->get()
                ->filter(fn (UserInvestment $investment) => $matchesRewardCap($investment, $rewardCap))
                ->values();

            $filename = 'shareholders-report-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($investments, $minerSlug, $status, $packageSlug, $search, $rewardCap, $resolveRewardCaps) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filter', 'Value']);
                fputcsv($handle, ['Miner', $minerSlug ?: 'all']);
                fputcsv($handle, ['Status', $status ?: 'all']);
                fputcsv($handle, ['Package', $packageSlug ?: 'all']);
                fputcsv($handle, ['Reward cap', $rewardCap ?: 'all']);
                fputcsv($handle, ['Search', $search !== '' ? $search : 'all']);
                fputcsv($handle, []);
                fputcsv($handle, ['Investor Name', 'Investor Email', 'Miner', 'Package', 'Amount', 'Shares', 'Return Rate', 'Reward Caps', 'Status', 'Subscribed At']);

                foreach ($investments as $investment) {
                    $caps = collect($resolveRewardCaps($investment->user))
                        ->filter()
                        ->keys()
                        ->map(fn ($key) => match ($key) {
                            'basic' => '4% cap',
                            'growth' => '6% cap',
                            'scale' => '7% cap',
                            default => $key,
                        })
                        ->implode(', ');

                    fputcsv($handle, [
                        $investment->user?->name,
                        $investment->user?->email,
                        $investment->miner?->name,
                        $investment->package?->name,
                        number_format((float) $investment->amount, 2, '.', ''),
                        (int) $investment->shares_owned,
                        number_format(((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate) * 100, 2, '.', '').'%',
                        $caps !== '' ? $caps : '—',
                        $investment->status,
                        optional($investment->subscribed_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.shareholders.export');
        Route::get('/dashboard/users', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $search = trim((string) $request->query('search', ''));
            $role = $request->query('role');
            $accountType = $request->query('account_type');
            $verification = $request->query('verification');
            $rewardCap = (string) $request->query('reward_cap', 'all');
            $auditFilter = (string) $request->query('audit_filter', 'all');
            $allowedRewardCaps = ['all', 'basic', 'growth', 'scale'];
            $allowedAuditFilters = ['all', 'locked_balance', 'unlocking_soon'];

            if (! in_array($rewardCap, $allowedRewardCaps, true)) {
                $rewardCap = 'all';
            }

            if (! in_array($auditFilter, $allowedAuditFilters, true)) {
                $auditFilter = 'all';
            }

            $rewardCapMeta = [
                'basic' => ['label' => 'Basic 100', 'short' => '4% cap'],
                'growth' => ['label' => 'Growth 500', 'short' => '6% cap'],
                'scale' => ['label' => 'Scale 1000+', 'short' => '7% cap'],
            ];
            $rewardCapCache = [];
            $resolveRewardCaps = function (User $user) use (&$rewardCapCache, $rewardCapMeta) {
                if (array_key_exists($user->id, $rewardCapCache)) {
                    return $rewardCapCache[$user->id];
                }

                $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
                $score = (int) (MiningPlatform::profilePowerSummary($user)['score'] ?? 0);
                $activeInvestments = $user->investments->where('status', 'active');

                return $rewardCapCache[$user->id] = [
                    'basic' => [
                        'key' => 'basic',
                        'label' => $rewardCapMeta['basic']['label'],
                        'short' => $rewardCapMeta['basic']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                    ],
                    'growth' => [
                        'key' => 'growth',
                        'label' => $rewardCapMeta['growth']['label'],
                        'short' => $rewardCapMeta['growth']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                    ],
                    'scale' => [
                        'key' => 'scale',
                        'label' => $rewardCapMeta['scale']['label'],
                        'short' => $rewardCapMeta['scale']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                    ],
                ];
            };

            $usersQuery = User::with(['userLevel', 'investments.package', 'earnings', 'friendInvitations', 'sponsoredUsers.investments', 'payoutRequests'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
                })
                ->when($role, fn ($query) => $query->where('role', $role))
                ->when($accountType, fn ($query) => $query->where('account_type', $accountType))
                ->when($verification === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->when($verification === 'unverified', fn ($query) => $query->whereNull('email_verified_at'));

            $baseUsers = $usersQuery->orderBy('created_at')->get();
            $allUsers = User::query()->get();
            $rewardCapBreakdown = collect($rewardCapMeta)->map(function (array $meta, string $key) use ($baseUsers, $resolveRewardCaps) {
                return [
                    'label' => $meta['label'],
                    'short' => $meta['short'],
                    'count' => $baseUsers->filter(fn (User $user) => ($resolveRewardCaps($user)[$key]['unlocked'] ?? false) === true)->count(),
                ];
            });
            $baseUserAuditStats = $baseUsers->mapWithKeys(function (User $user) {
                $activePaidInvestments = $user->investments
                    ->where('status', 'active')
                    ->filter(fn ($investment) => (float) $investment->amount > 0)
                    ->sortBy('subscribed_at')
                    ->values();

                $firstPaidInvestment = $activePaidInvestments->first();
                $upcomingUnlocks = $activePaidInvestments
                    ->map(function ($investment) {
                        $unlockDate = optional($investment->subscribed_at)?->copy()->addDays(30);

                        return [
                            'investment' => $investment,
                            'unlock_date' => $unlockDate,
                            'days_remaining' => $unlockDate ? max(now()->startOfDay()->diffInDays($unlockDate->copy()->startOfDay(), false), 0) : null,
                            'is_unlocked' => $unlockDate ? $unlockDate->lte(now()) : false,
                        ];
                    })
                    ->filter(fn (array $entry) => $entry['unlock_date'] !== null)
                    ->sortBy('unlock_date')
                    ->values();

                $nextUnlock = $upcomingUnlocks->first(fn (array $entry) => ! $entry['is_unlocked']);
                $lastPaidPayout = $user->payoutRequests
                    ->where('status', 'paid')
                    ->sortByDesc('processed_at')
                    ->first();

                return [$user->id => [
                    'first_paid_subscription_at' => $firstPaidInvestment?->subscribed_at,
                    'next_unlock_date' => $nextUnlock['unlock_date'] ?? null,
                    'days_to_unlock' => $nextUnlock['days_remaining'] ?? null,
                    'available_amount' => round((float) $user->earnings->where('status', 'available')->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'locked_amount' => round((float) $user->earnings->whereIn('status', ['pending', 'payout_pending'])->where('source', '!=', 'projected_return')->sum('amount'), 2),
                    'paid_amount' => round((float) $user->earnings->where('status', 'paid')->sum('amount'), 2),
                    'last_paid_payout_at' => $lastPaidPayout?->processed_at,
                    'last_paid_payout_amount' => $lastPaidPayout ? (float) $lastPaidPayout->amount : null,
                ]];
            });
            $users = $baseUsers
                ->filter(function (User $user) use ($rewardCap, $resolveRewardCaps, $auditFilter, $baseUserAuditStats) {
                    if ($rewardCap !== 'all' && (($resolveRewardCaps($user)[$rewardCap]['unlocked'] ?? false) !== true)) {
                        return false;
                    }

                    $audit = $baseUserAuditStats[$user->id] ?? null;

                    return match ($auditFilter) {
                        'locked_balance' => (float) ($audit['locked_amount'] ?? 0) > 0,
                        'unlocking_soon' => ($audit['days_to_unlock'] ?? null) !== null && (int) $audit['days_to_unlock'] <= 7,
                        default => true,
                    };
                })
                ->values();
            $userRewardCaps = $users->mapWithKeys(function (User $user) use ($resolveRewardCaps) {
                $badges = collect($resolveRewardCaps($user))
                    ->filter(fn (array $cap) => $cap['unlocked'])
                    ->values()
                    ->all();

                return [$user->id => $badges];
            });
            $userAuditStats = $users->mapWithKeys(fn (User $user) => [$user->id => $baseUserAuditStats[$user->id] ?? []]);

            return view('pages.general.users', [
                'users' => $users,
                'search' => $search,
                'selectedRole' => $role,
                'selectedAccountType' => $accountType,
                'selectedVerification' => $verification,
                'selectedRewardCap' => $rewardCap,
                'selectedAuditFilter' => $auditFilter,
                'userBreakdown' => [
                    'admins' => $allUsers->where('role', 'admin')->count(),
                    'users' => $allUsers->where('role', 'user')->count(),
                    'verified' => $allUsers->whereNotNull('email_verified_at')->count(),
                    'shareholders' => $allUsers->where('account_type', 'shareholder')->count(),
                ],
                'rewardCapBreakdown' => $rewardCapBreakdown,
                'userRewardCaps' => $userRewardCaps,
                'userAuditStats' => $userAuditStats,
            ]);
        })->name('dashboard.users');

        Route::get('/dashboard/users/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $search = trim((string) $request->query('search', ''));
            $role = $request->query('role');
            $accountType = $request->query('account_type');
            $verification = $request->query('verification');
            $rewardCap = (string) $request->query('reward_cap', 'all');
            $allowedRewardCaps = ['all', 'basic', 'growth', 'scale'];

            if (! in_array($rewardCap, $allowedRewardCaps, true)) {
                $rewardCap = 'all';
            }

            $rewardCapCache = [];
            $resolveRewardCaps = function (User $user) use (&$rewardCapCache) {
                if (array_key_exists($user->id, $rewardCapCache)) {
                    return $rewardCapCache[$user->id];
                }

                $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
                $score = (int) (MiningPlatform::profilePowerSummary($user)['score'] ?? 0);
                $activeInvestments = $user->investments->where('status', 'active');

                return $rewardCapCache[$user->id] = [
                    'basic' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                    'growth' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                    'scale' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                ];
            };

            $users = User::with(['userLevel', 'investments.package', 'earnings', 'friendInvitations', 'sponsoredUsers.investments'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
                })
                ->when($role, fn ($query) => $query->where('role', $role))
                ->when($accountType, fn ($query) => $query->where('account_type', $accountType))
                ->when($verification === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->when($verification === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
                ->orderBy('created_at')
                ->get()
                ->filter(fn (User $user) => $rewardCap === 'all' || (($resolveRewardCaps($user)[$rewardCap] ?? false) === true))
                ->values();

            $filename = 'users-report-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($users, $search, $role, $accountType, $verification, $rewardCap, $resolveRewardCaps) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filter', 'Value']);
                fputcsv($handle, ['Search', $search !== '' ? $search : 'all']);
                fputcsv($handle, ['Role', $role ?: 'all']);
                fputcsv($handle, ['Account type', $accountType ?: 'all']);
                fputcsv($handle, ['Verification', $verification ?: 'all']);
                fputcsv($handle, ['Reward cap', $rewardCap ?: 'all']);
                fputcsv($handle, []);
                fputcsv($handle, ['Name', 'Email', 'Role', 'Verification', 'Level', 'Account Type', 'Reward Caps', 'Total Invested', 'Available Earnings', 'Joined']);

                foreach ($users as $listedUser) {
                    $caps = collect($resolveRewardCaps($listedUser))
                        ->filter()
                        ->keys()
                        ->map(fn ($key) => match ($key) {
                            'basic' => '4% cap',
                            'growth' => '6% cap',
                            'scale' => '7% cap',
                            default => $key,
                        })
                        ->implode(', ');

                    fputcsv($handle, [
                        $listedUser->name,
                        $listedUser->email,
                        $listedUser->role,
                        $listedUser->email_verified_at ? 'verified' : 'pending',
                        $listedUser->userLevel?->name ?? 'Starter',
                        $listedUser->account_type,
                        $caps !== '' ? $caps : '—',
                        number_format((float) $listedUser->investments->where('status', 'active')->sum('amount'), 2, '.', ''),
                        number_format((float) $listedUser->earnings->where('status', 'available')->sum('amount'), 2, '.', ''),
                        optional($listedUser->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.users.export');

        Route::get('/dashboard/user-activity', function (Request $request) {
            abort_unless($request->user()?->isAdmin(), 403);

            $search = trim((string) $request->query('search', ''));
            $selectedUserId = (int) $request->query('user_id', 0);

            $usersQuery = User::query()
                ->withCount('friendInvitations')
                ->orderBy('name');

            if ($search !== '') {
                $usersQuery->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $usersQuery->get();

            $loginStats = UserLoginEvent::query()
                ->selectRaw('user_id, COUNT(*) as login_count, MAX(login_at) as last_login_at')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $pageTimeStats = UserPageActivityLog::query()
                ->selectRaw('user_id, SUM(seconds_spent) as total_seconds')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $topPagesByUser = UserPageActivityLog::query()
                ->selectRaw('user_id, path, SUM(seconds_spent) as total_seconds')
                ->groupBy('user_id', 'path')
                ->get()
                ->groupBy('user_id')
                ->map(function ($rows) {
                    return $rows->sortByDesc('total_seconds')->take(3)->values();
                });

            $activityRows = $users->map(function (User $user) use ($loginStats, $pageTimeStats, $topPagesByUser) {
                $loginStat = $loginStats->get($user->id);
                $pageStat = $pageTimeStats->get($user->id);

                return [
                    'user' => $user,
                    'login_count' => (int) ($loginStat->login_count ?? 0),
                    'last_login_at' => filled($loginStat->last_login_at ?? null) ? Carbon::parse($loginStat->last_login_at) : null,
                    'invitation_count' => (int) $user->friend_invitations_count,
                    'total_seconds' => (int) ($pageStat->total_seconds ?? 0),
                    'top_pages' => $topPagesByUser->get($user->id, collect()),
                ];
            })->values();

            $selectedUser = $selectedUserId > 0 ? $users->firstWhere('id', $selectedUserId) : null;
            $selectedUserPageBreakdown = collect();
            $selectedUserRecentLogins = collect();

            if ($selectedUser) {
                $selectedUserPageBreakdown = UserPageActivityLog::query()
                    ->where('user_id', $selectedUser->id)
                    ->selectRaw('path, route_name, SUM(seconds_spent) as total_seconds, MAX(ended_at) as last_seen_at')
                    ->groupBy('path', 'route_name')
                    ->orderByDesc('total_seconds')
                    ->limit(20)
                    ->get()
                    ->map(function ($row) {
                        $row->last_seen_at = filled($row->last_seen_at) ? Carbon::parse($row->last_seen_at) : null;

                        return $row;
                    });

                $selectedUserRecentLogins = UserLoginEvent::query()
                    ->where('user_id', $selectedUser->id)
                    ->latest('login_at')
                    ->limit(10)
                    ->get();
            }

            return view('pages.general.user-activity', [
                'activityRows' => $activityRows,
                'activitySearch' => $search,
                'selectedUser' => $selectedUser,
                'selectedUserPageBreakdown' => $selectedUserPageBreakdown,
                'selectedUserRecentLogins' => $selectedUserRecentLogins,
                'totalTrackedUsers' => $activityRows->count(),
                'totalLogins' => $activityRows->sum('login_count'),
                'totalInvitations' => $activityRows->sum('invitation_count'),
                'totalTrackedHours' => round($activityRows->sum('total_seconds') / 3600, 1),
            ]);
        })->name('dashboard.user-activity');

        Route::post('/dashboard/users/{user}/role', function (Request $request, User $user) {
            $validated = $request->validate([
                'role' => ['required', 'in:user,admin'],
            ]);

            $user->forceFill(['role' => $validated['role']])->save();

            return redirect()->route('dashboard.users')->with('users_success', $user->email.' role updated to '.$validated['role'].'.');
        })->name('dashboard.users.role');

        Route::get('/dashboard/operations', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $status = $request->query('investment_status', 'all');
            $allowedStatuses = ['all', 'pending', 'approved', 'rejected', 'cancelled'];
            $search = trim((string) $request->query('investment_search', ''));
            $paymentMethod = (string) $request->query('investment_payment_method', 'all');
            $allowedPaymentMethods = ['all', 'btc_transfer', 'usdt_transfer', 'bank_transfer'];
            $proofState = (string) $request->query('investment_proof_state', 'all');
            $allowedProofStates = ['all', 'proof_needed', 'proof_uploaded'];
            $riskState = (string) $request->query('investment_risk_state', 'all');
            $allowedRiskStates = ['all', 'high_risk'];
            $riskFocus = (string) $request->query('investment_risk_focus', 'all');
            $allowedRiskFocuses = ['all', 'missing_proof', 'bank_without_notes', 'resubmitted', 'override_history'];
            $activityAction = (string) $request->query('activity_action', 'all');
            $allowedActivityActions = ['all', 'investment.approve', 'investment.approve_without_proof', 'investment.reject', 'payout.approve', 'payout.pay'];

            if (! in_array($status, $allowedStatuses, true)) {
                $status = 'all';
            }

            if (! in_array($paymentMethod, $allowedPaymentMethods, true)) {
                $paymentMethod = 'all';
            }

            if (! in_array($proofState, $allowedProofStates, true)) {
                $proofState = 'all';
            }

            if (! in_array($riskState, $allowedRiskStates, true)) {
                $riskState = 'all';
            }

            if (! in_array($riskFocus, $allowedRiskFocuses, true)) {
                $riskFocus = 'all';
            }

            if (! in_array($activityAction, $allowedActivityActions, true)) {
                $activityAction = 'all';
            }

            $rewardCapMeta = [
                'basic' => ['label' => 'Basic 100', 'short' => '4% cap'],
                'growth' => ['label' => 'Growth 500', 'short' => '6% cap'],
                'scale' => ['label' => 'Scale 1000+', 'short' => '7% cap'],
            ];
            $rewardCapCache = [];
            $resolveRewardCaps = function (?User $user) use (&$rewardCapCache, $rewardCapMeta) {
                if (! $user) {
                    return collect($rewardCapMeta)->map(fn (array $meta, string $key) => [
                        'key' => $key,
                        'label' => $meta['label'],
                        'short' => $meta['short'],
                        'unlocked' => false,
                    ])->all();
                }

                if (array_key_exists($user->id, $rewardCapCache)) {
                    return $rewardCapCache[$user->id];
                }

                $user->loadMissing(['userLevel', 'friendInvitations', 'investments.package', 'sponsoredUsers.investments']);
                $score = (int) (MiningPlatform::profilePowerSummary($user)['score'] ?? 0);
                $activeInvestments = $user->investments->where('status', 'active');

                return $rewardCapCache[$user->id] = [
                    'basic' => [
                        'key' => 'basic',
                        'label' => $rewardCapMeta['basic']['label'],
                        'short' => $rewardCapMeta['basic']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount > 0 && (float) $investment->amount < 500),
                    ],
                    'growth' => [
                        'key' => 'growth',
                        'label' => $rewardCapMeta['growth']['label'],
                        'short' => $rewardCapMeta['growth']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 500 && (float) $investment->amount < 1000),
                    ],
                    'scale' => [
                        'key' => 'scale',
                        'label' => $rewardCapMeta['scale']['label'],
                        'short' => $rewardCapMeta['scale']['short'],
                        'unlocked' => $score >= 100 && $activeInvestments->contains(fn ($investment) => (float) $investment->amount >= 1000),
                    ],
                ];
            };

            $investmentOrdersQuery = InvestmentOrder::with(['user', 'package', 'miner', 'approver'])->latest('submitted_at');

            if ($status !== 'all') {
                $investmentOrdersQuery->where('status', $status);
            }

            if ($search !== '') {
                $investmentOrdersQuery->where(function ($query) use ($search) {
                    $query->where('payment_reference', 'like', "%$search%")
                        ->orWhere('payment_method', 'like', "%$search%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%"))
                        ->orWhereHas('package', fn ($packageQuery) => $packageQuery->where('name', 'like', "%$search%"))
                        ->orWhereHas('miner', fn ($minerQuery) => $minerQuery->where('name', 'like', "%$search%"));
                });
            }

            $investmentPaymentMethodCounts = (clone $investmentOrdersQuery)
                ->pluck('payment_method')
                ->filter()
                ->countBy();

            $investmentProofStateCounts = collect([
                'all' => (clone $investmentOrdersQuery)->count(),
                'proof_needed' => (clone $investmentOrdersQuery)->whereNull('payment_proof_path')->count(),
                'proof_uploaded' => (clone $investmentOrdersQuery)->whereNotNull('payment_proof_path')->count(),
            ]);

            $investmentRiskStateCounts = collect([
                'all' => (clone $investmentOrdersQuery)->count(),
                'high_risk' => (clone $investmentOrdersQuery)
                    ->where(function ($query) {
                        $query->where(function ($riskQuery) {
                            $riskQuery->where('status', 'pending')->whereNull('payment_proof_path');
                        })->orWhere(function ($riskQuery) {
                            $riskQuery->where('payment_method', 'bank_transfer')->where(function ($notesQuery) {
                                $notesQuery->whereNull('notes')->orWhere('notes', '');
                            });
                        })->orWhere(function ($riskQuery) {
                            $riskQuery->whereNotNull('rejected_at')->where('status', 'pending');
                        })->orWhere(function ($riskQuery) {
                            $riskQuery->whereNotNull('approved_at')->whereNotNull('admin_notes')->whereNull('payment_proof_path');
                        });
                    })
                    ->count(),
            ]);

            $investmentRiskBreakdown = collect([
                'missing_proof' => (clone $investmentOrdersQuery)
                    ->where('status', 'pending')
                    ->whereNull('payment_proof_path')
                    ->count(),
                'bank_without_notes' => (clone $investmentOrdersQuery)
                    ->where('payment_method', 'bank_transfer')
                    ->where(function ($notesQuery) {
                        $notesQuery->whereNull('notes')->orWhere('notes', '');
                    })
                    ->count(),
                'resubmitted' => (clone $investmentOrdersQuery)
                    ->whereNotNull('rejected_at')
                    ->where('status', 'pending')
                    ->count(),
                'override_history' => (clone $investmentOrdersQuery)
                    ->whereNotNull('approved_at')
                    ->whereNotNull('admin_notes')
                    ->whereNull('payment_proof_path')
                    ->count(),
            ]);

            if ($paymentMethod !== 'all') {
                $investmentOrdersQuery->where('payment_method', $paymentMethod);
            }

            if ($proofState === 'proof_needed') {
                $investmentOrdersQuery->whereNull('payment_proof_path');
            } elseif ($proofState === 'proof_uploaded') {
                $investmentOrdersQuery->whereNotNull('payment_proof_path');
            }

            if ($riskState === 'high_risk') {
                $investmentOrdersQuery->where(function ($query) {
                    $query->where(function ($riskQuery) {
                        $riskQuery->where('status', 'pending')->whereNull('payment_proof_path');
                    })->orWhere(function ($riskQuery) {
                        $riskQuery->where('payment_method', 'bank_transfer')->where(function ($notesQuery) {
                            $notesQuery->whereNull('notes')->orWhere('notes', '');
                        });
                    })->orWhere(function ($riskQuery) {
                        $riskQuery->whereNotNull('rejected_at')->where('status', 'pending');
                    })->orWhere(function ($riskQuery) {
                        $riskQuery->whereNotNull('approved_at')->whereNotNull('admin_notes')->whereNull('payment_proof_path');
                    });
                });
            }

            if ($riskFocus === 'missing_proof') {
                $investmentOrdersQuery->where('status', 'pending')->whereNull('payment_proof_path');
            } elseif ($riskFocus === 'bank_without_notes') {
                $investmentOrdersQuery->where('payment_method', 'bank_transfer')->where(function ($notesQuery) {
                    $notesQuery->whereNull('notes')->orWhere('notes', '');
                });
            } elseif ($riskFocus === 'resubmitted') {
                $investmentOrdersQuery->whereNotNull('rejected_at')->where('status', 'pending');
            } elseif ($riskFocus === 'override_history') {
                $investmentOrdersQuery->whereNotNull('approved_at')->whereNotNull('admin_notes')->whereNull('payment_proof_path');
            }

            $allInvestmentStatuses = InvestmentOrder::query()->pluck('status');
            $investmentOrders = $investmentOrdersQuery->get();
            $investmentOrderRewardCaps = $investmentOrders->mapWithKeys(function (InvestmentOrder $order) use ($resolveRewardCaps) {
                $badges = collect($resolveRewardCaps($order->user))
                    ->filter(fn (array $cap) => $cap['unlocked'])
                    ->values()
                    ->all();

                return [$order->id => $badges];
            });
            $investmentRewardCapSummary = collect($rewardCapMeta)->map(function (array $meta, string $key) use ($investmentOrders, $resolveRewardCaps) {
                return [
                    'label' => $meta['label'],
                    'short' => $meta['short'],
                    'count' => $investmentOrders->filter(fn (InvestmentOrder $order) => ($resolveRewardCaps($order->user)[$key]['unlocked'] ?? false) === true)->count(),
                ];
            });
            $investmentPaymentMethodSummaries = $investmentOrders
                ->groupBy('payment_method')
                ->map(function ($orders, $method) {
                    return [
                        'key' => $method,
                        'label' => str_replace('_', ' ', (string) $method),
                        'count' => $orders->count(),
                        'note' => MiningPlatform::platformSetting('payment_'.$method.'_admin_review_note'),
                    ];
                })
                ->sortByDesc('count')
                ->values();
            $adminActivityLogsQuery = AdminActivityLog::query()
                ->with('admin')
                ->latest();

            if ($activityAction !== 'all') {
                $adminActivityLogsQuery->where('action', $activityAction);
            }

            $adminActivityLogs = $adminActivityLogsQuery->limit(20)->get();

            $investorPayoutBoard = User::query()
                ->whereHas('investments', fn ($query) => $query->where('status', 'active'))
                ->with([
                    'investments' => fn ($query) => $query->where('status', 'active')->with('package')->orderBy('subscribed_at'),
                    'earnings',
                ])
                ->orderBy('name')
                ->get()
                ->map(function (User $investor) {
                    $activeInvestments = $investor->investments->where('status', 'active')->values();
                    $firstInvestmentDate = $activeInvestments->min('subscribed_at');
                    $anchorDate = $firstInvestmentDate instanceof Carbon
                        ? $firstInvestmentDate->copy()->startOfDay()
                        : null;
                    $nextPayoutDate = $anchorDate?->copy();

                    while ($nextPayoutDate instanceof Carbon && $nextPayoutDate->lte(now()->startOfDay())) {
                        $nextPayoutDate->addMonthNoOverflow();
                    }

                    $availableToWithdraw = (float) $investor->earnings->where('status', 'available')->sum('amount');
                    $projectedNextPayout = (float) $activeInvestments->sum(fn (UserInvestment $investment) => MiningPlatform::investmentProjectedRewardAmount($investment));

                    return [
                        'user' => $investor,
                        'active_investment_count' => $activeInvestments->count(),
                        'first_investment_date' => $firstInvestmentDate instanceof Carbon ? $firstInvestmentDate : null,
                        'next_payout_date' => $nextPayoutDate instanceof Carbon ? $nextPayoutDate : null,
                        'days_until_next_payout' => $nextPayoutDate instanceof Carbon ? max(now()->startOfDay()->diffInDays($nextPayoutDate, false), 0) : null,
                        'available_to_withdraw' => $availableToWithdraw,
                        'projected_next_payout' => $projectedNextPayout,
                        'active_package_names' => $activeInvestments->pluck('package.name')->filter()->unique()->values(),
                    ];
                })
                ->values();

            return view('pages.general.operations', [
                'payoutRequests' => PayoutRequest::with(['user', 'earnings'])->latest('requested_at')->get(),
                'investmentOrders' => $investmentOrders,
                'investmentPaymentMethodSummaries' => $investmentPaymentMethodSummaries,
                'investmentFilters' => [
                    'status' => $status,
                    'search' => $search,
                    'payment_method' => $paymentMethod,
                    'proof_state' => $proofState,
                    'risk_state' => $riskState,
                ],
                'investmentPaymentMethodCounts' => collect([
                    'all' => $investmentPaymentMethodCounts->sum(),
                    'btc_transfer' => $investmentPaymentMethodCounts->get('btc_transfer', 0),
                    'usdt_transfer' => $investmentPaymentMethodCounts->get('usdt_transfer', 0),
                    'bank_transfer' => $investmentPaymentMethodCounts->get('bank_transfer', 0),
                ]),
                'investmentProofStateCounts' => $investmentProofStateCounts,
                'investmentRiskStateCounts' => $investmentRiskStateCounts,
                'investmentOrderCounts' => [
                    'all' => $allInvestmentStatuses->count(),
                    'pending' => $allInvestmentStatuses->filter(fn ($orderStatus) => $orderStatus === 'pending')->count(),
                    'approved' => $allInvestmentStatuses->filter(fn ($orderStatus) => $orderStatus === 'approved')->count(),
                    'rejected' => $allInvestmentStatuses->filter(fn ($orderStatus) => $orderStatus === 'rejected')->count(),
                    'cancelled' => $allInvestmentStatuses->filter(fn ($orderStatus) => $orderStatus === 'cancelled')->count(),
                ],
                'investmentPaymentMethodReviews' => collect([
                    'btc_transfer' => MiningPlatform::platformSetting('payment_btc_transfer_admin_review_note'),
                    'usdt_transfer' => MiningPlatform::platformSetting('payment_usdt_transfer_admin_review_note'),
                    'bank_transfer' => MiningPlatform::platformSetting('payment_bank_transfer_admin_review_note'),
                ]),
                'investmentOrderRewardCaps' => $investmentOrderRewardCaps,
                'investmentRewardCapSummary' => $investmentRewardCapSummary,
                'adminActivityLogs' => $adminActivityLogs,
                'activityFilters' => [
                    'action' => $activityAction,
                ],
                'investorPayoutBoard' => $investorPayoutBoard,
            ]);
        })->name('dashboard.operations');

        $securityCenterPayload = function (User $admin): array {
            MiningPlatform::ensureDefaults();
            $currentHealthSummary = MiningPlatform::adminHealthSummary();
            $adminNotifications = $admin->notifications()
                ->latest()
                ->limit(50)
                ->get()
                ->filter(fn ($notification) => ($notification->data['category'] ?? null) === 'admin')
                ->values();

            $criticalAlerts = $adminNotifications
                ->filter(fn ($notification) => ($notification->data['status'] ?? null) === 'warning')
                ->values();

            $healthSummaryNotifications = $adminNotifications
                ->filter(fn ($notification) => ($notification->data['subject'] ?? null) === 'Daily admin health summary')
                ->values();

            return [
                'currentHealthSummary' => $currentHealthSummary,
                'criticalAlerts' => $criticalAlerts->take(10),
                'healthSummaryNotifications' => $healthSummaryNotifications->take(5),
                'recentAdminActivityLogs' => AdminActivityLog::query()
                    ->with('admin')
                    ->latest()
                    ->limit(15)
                    ->get(),
            ];
        };

        Route::get('/dashboard/security-center', function (Request $request) use ($securityCenterPayload) {
            return view('pages.general.security-center', $securityCenterPayload($request->user()));
        })->name('dashboard.security-center');

        Route::get('/dashboard/security-center/export/word', function (Request $request) use ($securityCenterPayload) {
            $content = view('pages.general.security-center-export', $securityCenterPayload($request->user()) + [
                'autoPrint' => false,
            ])->render();

            return response($content, 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="zagchain-security-center-'.now()->format('Ymd-His').'.doc"',
            ]);
        })->name('dashboard.security-center.export.word');

        Route::get('/dashboard/security-center/export/pdf', function (Request $request) use ($securityCenterPayload) {
            return response(
                view('pages.general.security-center-export', $securityCenterPayload($request->user()) + [
                    'autoPrint' => true,
                ])->render(),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        })->name('dashboard.security-center.export.pdf');

        Route::get('/dashboard/operations/export', function () {
            MiningPlatform::ensureDefaults();

            $payoutRequests = PayoutRequest::with(['user', 'earnings'])->latest('requested_at')->get();
            $filename = 'payout-operations-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($payoutRequests) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['User Name', 'User Email', 'Requested At', 'Amount', 'Method', 'Destination', 'Status', 'Approved At', 'Paid At', 'Transaction Reference', 'Admin Notes']);

                foreach ($payoutRequests as $request) {
                    fputcsv($handle, [
                        $request->user?->name,
                        $request->user?->email,
                        optional($request->requested_at)->format('Y-m-d H:i:s'),
                        number_format((float) $request->amount, 2, '.', ''),
                        $request->method,
                        $request->destination,
                        $request->status,
                        optional($request->approved_at)->format('Y-m-d H:i:s'),
                        optional($request->processed_at)->format('Y-m-d H:i:s'),
                        $request->transaction_reference,
                        $request->admin_notes,
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.operations.export');

        Route::get('/dashboard/operations/investment-orders/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $status = $request->query('investment_status', 'all');
            $allowedStatuses = ['all', 'pending', 'approved', 'rejected', 'cancelled'];
            $search = trim((string) $request->query('investment_search', ''));
            $paymentMethod = (string) $request->query('investment_payment_method', 'all');
            $allowedPaymentMethods = ['all', 'btc_transfer', 'usdt_transfer', 'bank_transfer'];

            if (! in_array($status, $allowedStatuses, true)) {
                $status = 'all';
            }


            if (! in_array($paymentMethod, $allowedPaymentMethods, true)) {
                $paymentMethod = 'all';
            }
            $investmentOrdersQuery = InvestmentOrder::with(['user', 'package', 'miner', 'approver'])->latest('submitted_at');

            if ($status !== 'all') {
                $investmentOrdersQuery->where('status', $status);
            }

            if ($search !== '') {
                $investmentOrdersQuery->where(function ($query) use ($search) {
                    $query->where('payment_reference', 'like', "%$search%")
                        ->orWhere('payment_method', 'like', "%$search%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%"))
                        ->orWhereHas('package', fn ($packageQuery) => $packageQuery->where('name', 'like', "%$search%"))
                        ->orWhereHas('miner', fn ($minerQuery) => $minerQuery->where('name', 'like', "%$search%"));
                });
            }

            if ($paymentMethod !== 'all') {
                $investmentOrdersQuery->where('payment_method', $paymentMethod);
            }

            $investmentOrders = $investmentOrdersQuery->get();
            $filename = 'investment-orders-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($investmentOrders, $status, $search, $paymentMethod) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Filter', 'Value']);
                fputcsv($handle, ['Status', $status]);
                fputcsv($handle, ['Search', $search !== '' ? $search : 'all']);
                fputcsv($handle, ['Payment method', $paymentMethod]);
                fputcsv($handle, []);
                fputcsv($handle, ['User Name', 'User Email', 'Package', 'Miner', 'Submitted At', 'Amount', 'Shares', 'Payment Method', 'Payment Reference', 'Proof Status', 'Status', 'Reviewed By', 'Approved At', 'Rejected At', 'Cancelled At', 'Admin Notes']);

                foreach ($investmentOrders as $order) {
                    fputcsv($handle, [
                        $order->user?->name,
                        $order->user?->email,
                        $order->package?->name,
                        $order->miner?->name,
                        optional($order->submitted_at)->format('Y-m-d H:i:s'),
                        number_format((float) $order->amount, 2, '.', ''),
                        $order->shares_owned,
                        $order->payment_method,
                        $order->payment_reference,
                        $order->payment_proof_path ? 'uploaded' : 'missing',
                        $order->status,
                        $order->approver?->adminLabel(),
                        optional($order->approved_at)->format('Y-m-d H:i:s'),
                        optional($order->rejected_at)->format('Y-m-d H:i:s'),
                        optional($order->cancelled_at)->format('Y-m-d H:i:s'),
                        $order->admin_notes,
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.operations.investment-orders.export');

        Route::post('/dashboard/operations/investment-orders/bulk', function (Request $request) {
            $validated = $request->validate([
                'action' => ['required', 'string', 'in:approve,reject'],
                'order_ids' => ['required', 'array', 'min:1'],
                'order_ids.*' => ['integer', 'exists:investment_orders,id'],
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $selectedOrders = InvestmentOrder::with(['user', 'package'])
                ->whereIn('id', $validated['order_ids'])
                ->get();

            $pendingOrders = $selectedOrders->where('status', 'pending')->values();
            $skippedCount = $selectedOrders->count() - $pendingOrders->count();

            if ($pendingOrders->isEmpty()) {
                return redirect()->route('dashboard.operations')->withErrors([
                    'approval' => 'Only pending investment orders can be processed in bulk.',
                ]);
            }

            if ($validated['action'] === 'approve') {
                $approvableOrders = $pendingOrders->filter(fn ($order) => filled($order->payment_proof_path))->values();
                $skippedCount += $pendingOrders->count() - $approvableOrders->count();

                if ($approvableOrders->isEmpty()) {
                    return redirect()->route('dashboard.operations')->withErrors([
                        'approval' => 'No selected pending orders had uploaded proof for bulk approval.',
                    ]);
                }

                foreach ($approvableOrders as $order) {
                    MiningPlatform::approveInvestmentOrder($order, $request->user());

                    $approvedOrder = $order->fresh(['user', 'package']);
                    $approvedTemplate = MiningPlatform::activityTemplate('investment_payment_approved', [
                        'package_name' => $approvedOrder->package?->name ?? 'investment',
                    ]);

                    $approvedOrder?->user?->notify(new ActivityFeedNotification([
                        'category' => 'investment',
                        'status' => 'success',
                        'subject' => $approvedTemplate['subject'],
                        'message' => $approvedTemplate['message'],
                        'context_label' => 'Approved package',
                        'context_value' => $approvedOrder->package?->name ?? 'Investment package',
                        'amount' => (float) $approvedOrder->amount,
                        'amount_label' => 'Approved amount',
                        'force_mail' => true,
                    ]));
                }

                return redirect()->route('dashboard.operations')->with('operations_success', 'Bulk approval complete: '.$approvableOrders->count().' approved, '.$skippedCount.' skipped.');
            }

            if (blank($validated['admin_notes'] ?? null)) {
                return redirect()->route('dashboard.operations')->withErrors([
                    'admin_notes' => 'Admin notes are required for bulk rejection.',
                ]);
            }

            foreach ($pendingOrders as $order) {
                MiningPlatform::rejectInvestmentOrder($order, $request->user(), $validated['admin_notes']);
            }

            return redirect()->route('dashboard.operations')->with('operations_success', 'Bulk rejection complete: '.$pendingOrders->count().' rejected, '.$skippedCount.' skipped.');
        })->name('dashboard.operations.investment-orders.bulk');

        Route::post('/dashboard/operations/payouts/{payoutRequest}/approve', function (Request $request, PayoutRequest $payoutRequest) {
            $validated = $request->validate([
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $payoutRequest = MiningPlatform::approvePayoutRequest($payoutRequest, $validated['admin_notes'] ?? null);
            MiningPlatform::logAdminActivity(
                $request->user(),
                'payout.approve',
                'Approved payout request #'.$payoutRequest->id,
                $payoutRequest,
                [
                    'user_id' => $payoutRequest->user_id,
                    'method' => $payoutRequest->method,
                    'amount' => (float) $payoutRequest->amount,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ],
            );
            $payoutRequest->user?->notify(new PayoutStatusNotification($payoutRequest, 'approved'));

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request approved successfully.');
        })->middleware('throttle:20,1')->name('dashboard.operations.payouts.approve');

        Route::post('/dashboard/operations/payouts/{payoutRequest}/pay', function (Request $request, PayoutRequest $payoutRequest) {
            $validated = $request->validate([
                'transaction_reference' => ['nullable', 'string', 'max:255'],
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $payoutRequest = MiningPlatform::markPayoutRequestPaid(
                $payoutRequest,
                $validated['transaction_reference'] ?? null,
                $validated['admin_notes'] ?? null,
            );
            MiningPlatform::logAdminActivity(
                $request->user(),
                'payout.pay',
                'Marked payout request #'.$payoutRequest->id.' as paid',
                $payoutRequest,
                [
                    'user_id' => $payoutRequest->user_id,
                    'method' => $payoutRequest->method,
                    'amount' => (float) $payoutRequest->amount,
                    'transaction_reference' => $validated['transaction_reference'] ?? null,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ],
            );

            $payoutRequest->user?->notify(new PayoutStatusNotification($payoutRequest, 'paid'));

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request marked as paid.');
        })->middleware('throttle:20,1')->name('dashboard.operations.payouts.pay');

        Route::post('/dashboard/operations/investment-orders/{investmentOrder}/approve', function (Request $request, InvestmentOrder $investmentOrder) {
            $validated = $request->validate([
                'allow_without_proof' => ['nullable', 'boolean'],
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $allowWithoutProof = (bool) ($validated['allow_without_proof'] ?? false);

            if (! $investmentOrder->payment_proof_path && ! $allowWithoutProof) {
                return redirect()->route('dashboard.operations')->withErrors([
                    'approval' => 'Payment proof must be uploaded before standard approval.',
                ]);
            }

            if (! $investmentOrder->payment_proof_path && blank($validated['admin_notes'] ?? null)) {
                return redirect()->route('dashboard.operations')->withErrors([
                    'admin_notes' => 'Admin notes are required when approving without proof.',
                ]);
            }

            MiningPlatform::approveInvestmentOrder(
                $investmentOrder,
                $request->user(),
                $allowWithoutProof,
                $validated['admin_notes'] ?? null,
            );

            $investmentOrder = $investmentOrder->fresh(['user', 'package']);
            MiningPlatform::logAdminActivity(
                $request->user(),
                $allowWithoutProof ? 'investment.approve_without_proof' : 'investment.approve',
                'Approved investment order #'.$investmentOrder->id,
                $investmentOrder,
                [
                    'user_id' => $investmentOrder->user_id,
                    'package' => $investmentOrder->package?->slug,
                    'amount' => (float) $investmentOrder->amount,
                    'allow_without_proof' => $allowWithoutProof,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ],
            );

            if ($allowWithoutProof) {
                $overrideTemplate = MiningPlatform::activityTemplate('investment_payment_override', [
                    'package_name' => $investmentOrder->package?->name ?? 'investment',
                ]);

                $investmentOrder?->user?->notify(new ActivityFeedNotification([
                    'category' => 'investment',
                    'status' => 'warning',
                    'subject' => $overrideTemplate['subject'],
                    'message' => $overrideTemplate['message'],
                    'context_label' => 'Admin reason',
                    'context_value' => $validated['admin_notes'] ?? 'No reason provided.',
                    'amount' => (float) $investmentOrder->amount,
                    'amount_label' => 'Approved amount',
                    'force_mail' => true,
                ]));
            } else {
                $approvedTemplate = MiningPlatform::activityTemplate('investment_payment_approved', [
                    'package_name' => $investmentOrder->package?->name ?? 'investment',
                ]);

                $investmentOrder?->user?->notify(new ActivityFeedNotification([
                    'category' => 'investment',
                    'status' => 'success',
                    'subject' => $approvedTemplate['subject'],
                    'message' => $approvedTemplate['message'],
                    'context_label' => 'Approved package',
                    'context_value' => $investmentOrder->package?->name ?? 'Investment package',
                    'amount' => (float) $investmentOrder->amount,
                    'amount_label' => 'Approved amount',
                    'force_mail' => true,
                ]));
            }

            return redirect()->route('dashboard.operations')->with('operations_success', $allowWithoutProof ? 'Investment order approved without proof override.' : 'Investment order approved successfully.');
        })->middleware('throttle:20,1')->name('dashboard.operations.investment-orders.approve');
        Route::post('/dashboard/operations/investment-orders/{investmentOrder}/reject', function (Request $request, InvestmentOrder $investmentOrder) {
            $validated = $request->validate([
                'admin_notes' => ['required', 'string', 'max:1000'],
            ]);

            MiningPlatform::rejectInvestmentOrder($investmentOrder, $request->user(), $validated['admin_notes']);
            $investmentOrder = $investmentOrder->fresh(['package']);
            MiningPlatform::logAdminActivity(
                $request->user(),
                'investment.reject',
                'Rejected investment order #'.$investmentOrder->id,
                $investmentOrder,
                [
                    'user_id' => $investmentOrder->user_id,
                    'package' => $investmentOrder->package?->slug,
                    'amount' => (float) $investmentOrder->amount,
                    'admin_notes' => $validated['admin_notes'],
                ],
            );

            return redirect()->route('dashboard.operations')->with('operations_success', 'Investment order rejected successfully.');
        })->middleware('throttle:20,1')->name('dashboard.operations.investment-orders.reject');

        Route::get('/dashboard/rewards', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.rewards', [
                'settings' => MiningPlatform::rewardSettings(),
            ]);
        })->name('dashboard.rewards');

        Route::get('/dashboard/rewards/referral-registration-guide', function () {
            $markdownPath = base_path('REFERRAL_REGISTRATION_REWARD_SYSTEM.md');
            $csvPath = base_path('REFERRAL_REGISTRATION_REWARD_EXAMPLES.csv');

            $explanation = File::exists($markdownPath)
                ? trim((string) File::get($markdownPath))
                : '';
            $explanationLines = collect(preg_split("/\r\n|\n|\r/", $explanation))
                ->map(fn (?string $line) => trim((string) $line))
                ->filter()
                ->values();

            $csvRows = collect();

            if (File::exists($csvPath)) {
                $lines = collect(preg_split("/\r\n|\n|\r/", trim((string) File::get($csvPath))))->filter();
                $headers = $lines->isNotEmpty() ? str_getcsv((string) $lines->shift()) : [];
                $csvRows = $lines->map(function (string $line) use ($headers) {
                    $values = str_getcsv($line);

                    return collect($headers)->mapWithKeys(function ($header, $index) use ($values) {
                        return [$header => $values[$index] ?? ''];
                    });
                })->values();
            }

            return view('pages.general.referral-registration-reward-guide', [
                'rewardGuideMarkdown' => $explanation,
                'rewardGuideLines' => $explanationLines,
                'rewardGuideExamples' => $csvRows,
            ]);
        })->name('dashboard.rewards.referral-registration-guide');

        Route::get('/dashboard/rewards/referral-registration-guide/examples', function () {
            $csvPath = base_path('REFERRAL_REGISTRATION_REWARD_EXAMPLES.csv');
            abort_unless(File::exists($csvPath), 404);

            return response()->download($csvPath, 'referral-registration-reward-examples.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        })->name('dashboard.rewards.referral-registration-guide.examples');

        Route::post('/dashboard/rewards', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'free_starter_verified_invites_required' => ['required', 'integer', 'min:1'],
                'free_starter_direct_basic_required' => ['required', 'integer', 'min:1'],
                'referral_registration_reward' => ['required', 'numeric', 'min:0'],
                'referral_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_direct_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_indirect_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'invitation_bonus_after_10_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'invitation_bonus_after_20_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'invitation_bonus_after_50_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_bonus_after_1_investor_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_bonus_after_3_investor_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_bonus_after_5_investor_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_level_3_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_level_4_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'team_level_5_subscription_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'profile_power_basic_max_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'profile_power_growth_max_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'profile_power_scale_max_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            ]);

            MiningPlatform::updateRewardSettings($validated);

            return redirect()->route('dashboard.rewards')->with('rewards_success', 'Reward settings updated successfully.');
        })->name('dashboard.rewards.update');

        Route::get('/dashboard/settings', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.settings', [
                'settings' => MiningPlatform::platformSettings(),
            ]);
        })->name('dashboard.settings');

        Route::post('/dashboard/settings', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'new_miner_total_shares' => ['required', 'integer', 'min:1'],
                'new_miner_share_price' => ['required', 'numeric', 'min:1'],
                'new_miner_daily_output_usd' => ['required', 'numeric', 'min:0'],
                'new_miner_monthly_output_usd' => ['required', 'numeric', 'min:0'],
                'new_miner_base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'launch_package_name' => ['required', 'string', 'max:255'],
                'launch_package_shares_count' => ['required', 'integer', 'min:1'],
                'launch_package_units_limit' => ['required', 'integer', 'min:1'],
                'launch_package_price_multiplier' => ['required', 'numeric', 'min:0.1'],
                'launch_package_rate_bonus' => ['required', 'numeric', 'min:0', 'max:1'],
                'growth_package_name' => ['required', 'string', 'max:255'],
                'growth_package_shares_count' => ['required', 'integer', 'min:1'],
                'growth_package_units_limit' => ['required', 'integer', 'min:1'],
                'growth_package_price_multiplier' => ['required', 'numeric', 'min:0.1'],
                'growth_package_rate_bonus' => ['required', 'numeric', 'min:0', 'max:1'],
                'scale_package_name' => ['required', 'string', 'max:255'],
                'scale_package_shares_count' => ['required', 'integer', 'min:1'],
                'scale_package_units_limit' => ['required', 'integer', 'min:1'],
                'scale_package_price_multiplier' => ['required', 'numeric', 'min:0.1'],
                'scale_package_rate_bonus' => ['required', 'numeric', 'min:0', 'max:1'],
                'payout_btc_wallet_enabled' => ['required', 'boolean'],
                'payout_btc_wallet_label' => ['required', 'string', 'max:255'],
                'payout_btc_wallet_placeholder' => ['required', 'string', 'max:255'],
                'payout_btc_wallet_minimum_amount' => ['required', 'numeric', 'min:0'],
                'payout_btc_wallet_fixed_fee' => ['required', 'numeric', 'min:0'],
                'payout_btc_wallet_percentage_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'payout_btc_wallet_instruction' => ['required', 'string', 'max:255'],
                'payout_btc_wallet_processing_time' => ['required', 'string', 'max:255'],
                'payout_usdt_wallet_enabled' => ['required', 'boolean'],
                'payout_usdt_wallet_label' => ['required', 'string', 'max:255'],
                'payout_usdt_wallet_placeholder' => ['required', 'string', 'max:255'],
                'payout_usdt_wallet_minimum_amount' => ['required', 'numeric', 'min:0'],
                'payout_usdt_wallet_fixed_fee' => ['required', 'numeric', 'min:0'],
                'payout_usdt_wallet_percentage_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'payout_usdt_wallet_instruction' => ['required', 'string', 'max:255'],
                'payout_usdt_wallet_processing_time' => ['required', 'string', 'max:255'],
                'payout_bank_transfer_enabled' => ['required', 'boolean'],
                'payout_bank_transfer_label' => ['required', 'string', 'max:255'],
                'payout_bank_transfer_placeholder' => ['required', 'string', 'max:255'],
                'payout_bank_transfer_minimum_amount' => ['required', 'numeric', 'min:0'],
                'payout_bank_transfer_fixed_fee' => ['required', 'numeric', 'min:0'],
                'payout_bank_transfer_percentage_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'payout_bank_transfer_instruction' => ['required', 'string', 'max:255'],
                'payout_bank_transfer_processing_time' => ['required', 'string', 'max:255'],
                'payment_btc_transfer_enabled' => ['required', 'boolean'],
                'payment_btc_transfer_label' => ['required', 'string', 'max:255'],
                'payment_btc_transfer_destination' => ['required', 'string', 'max:255'],
                'payment_btc_transfer_reference_hint' => ['required', 'string', 'max:255'],
                'payment_btc_transfer_instruction' => ['required', 'string', 'max:255'],
                'payment_btc_transfer_admin_review_note' => ['required', 'string', 'max:255'],
                'payment_usdt_transfer_enabled' => ['required', 'boolean'],
                'payment_usdt_transfer_label' => ['required', 'string', 'max:255'],
                'payment_usdt_transfer_destination' => ['required', 'string', 'max:255'],
                'payment_usdt_transfer_reference_hint' => ['required', 'string', 'max:255'],
                'payment_usdt_transfer_instruction' => ['required', 'string', 'max:255'],
                'payment_usdt_transfer_admin_review_note' => ['required', 'string', 'max:255'],
                'payment_bank_transfer_enabled' => ['required', 'boolean'],
                'payment_bank_transfer_label' => ['required', 'string', 'max:255'],
                'payment_bank_transfer_destination' => ['required', 'string', 'max:255'],
                'payment_bank_transfer_reference_hint' => ['required', 'string', 'max:255'],
                'payment_bank_transfer_instruction' => ['required', 'string', 'max:255'],
                'payment_bank_transfer_admin_review_note' => ['required', 'string', 'max:255'],
            ]);

            MiningPlatform::updatePlatformSettings($validated);

            return redirect()->route('dashboard.settings')->with('settings_success', 'Platform defaults updated successfully.');
        })->name('dashboard.settings.update');

        Route::get('/dashboard/notification-rules', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.notification-rules', [
                'settings' => MiningPlatform::platformSettings(),
            ]);
        })->name('dashboard.notification-rules');

        Route::post('/dashboard/notification-rules', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'notification_payout_in_app' => ['nullable', 'boolean'],
                'notification_payout_email' => ['nullable', 'boolean'],
                'notification_reward_in_app' => ['nullable', 'boolean'],
                'notification_reward_email' => ['nullable', 'boolean'],
                'notification_investment_in_app' => ['nullable', 'boolean'],
                'notification_investment_email' => ['nullable', 'boolean'],
                'notification_network_in_app' => ['nullable', 'boolean'],
                'notification_network_email' => ['nullable', 'boolean'],
                'notification_milestone_in_app' => ['nullable', 'boolean'],
                'notification_milestone_email' => ['nullable', 'boolean'],
            ]);

            MiningPlatform::updatePlatformSettings($validated);

            return redirect()->route('dashboard.notification-rules')->with('notification_rules_success', 'Notification rules updated successfully.');
        })->name('dashboard.notification-rules.update');

        Route::get('/dashboard/notification-templates', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.notification-templates', [
                'settings' => MiningPlatform::platformSettings(),
            ]);
        })->name('dashboard.notification-templates');

        Route::post('/dashboard/notification-templates', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $templateKeys = [
                'payout_submitted', 'payout_approved', 'payout_paid',
                'free_starter', 'network_join', 'reward_registration',
                'network_sponsor', 'basic_unlocked', 'investment_activated',
                'team_level_1', 'team_level_2', 'team_level_generic',
            ];

            $rules = [];

            foreach ($templateKeys as $templateKey) {
                $rules['template_'.$templateKey.'_subject'] = ['required', 'string', 'max:255'];
                $rules['template_'.$templateKey.'_message'] = ['required', 'string'];
            }

            $validated = $request->validate($rules);
            MiningPlatform::updatePlatformSettings($validated);

            return redirect()->route('dashboard.notification-templates')->with('notification_templates_success', 'Notification templates updated successfully.');
        })->name('dashboard.notification-templates.update');

        Route::post('/dashboard/notification-templates/preview', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'template_key' => ['required', 'string', 'in:payout_submitted,payout_approved,payout_paid,free_starter,network_join,reward_registration,network_sponsor,basic_unlocked,investment_activated,team_level_1,team_level_2,team_level_generic'],
            ]);

            $templateKey = $validated['template_key'];
            $category = match ($templateKey) {
                'payout_submitted', 'payout_approved', 'payout_paid' => 'payout',
                'investment_activated' => 'investment',
                'network_join', 'network_sponsor' => 'network',
                'free_starter', 'basic_unlocked' => 'milestone',
                default => 'reward',
            };

            $template = MiningPlatform::activityTemplate($templateKey, [
                'user_name' => 'Preview Investor',
                'user_email' => 'preview@example.com',
                'package_name' => 'Basic 100',
                'level' => 3,
                'sponsor_name' => 'Preview Sponsor',
                'sponsor_email' => 'sponsor@example.com',
                'gross_amount' => '$100.00',
                'fee_amount' => '$5.00',
                'net_amount' => '$95.00',
                'method_label' => 'BTC Wallet',
                'destination' => 'bc1-preview-wallet',
            ]);

            $request->user()->notify(new ActivityFeedNotification([
                'category' => $category,
                'status' => 'info',
                'subject' => $template['subject'],
                'message' => $template['message'],
                'context_label' => 'Preview event',
                'context_value' => str($templateKey)->replace('_', ' ')->title()->toString(),
                'is_preview' => true,
            ]));

            return redirect()->route('dashboard.notification-templates')->with('notification_templates_success', 'Preview notification sent to your dashboard feed.');
        })->name('dashboard.notification-templates.preview');

        Route::get('/dashboard/packages', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.packages', [
                'packages' => InvestmentPackage::with('miner')->orderBy('display_order')->get(),
                'miners' => Miner::orderBy('name')->get(),
            ]);
        })->name('dashboard.packages');

        Route::post('/dashboard/packages', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_id' => ['required', 'exists:miners,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:investment_packages,slug'],
                'price' => ['required', 'numeric', 'min:1'],
                'shares_count' => ['required', 'integer', 'min:1'],
                'units_limit' => ['required', 'integer', 'min:1'],
                'monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'display_order' => ['required', 'integer', 'min:1'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $package = InvestmentPackage::create([
                'miner_id' => $validated['miner_id'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'price' => $validated['price'],
                'shares_count' => $validated['shares_count'],
                'units_limit' => $validated['units_limit'],
                'monthly_return_rate' => $validated['monthly_return_rate'],
                'display_order' => $validated['display_order'],
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('dashboard.packages')->with('packages_success', $package->name.' created successfully.');
        })->name('dashboard.packages.store');

        Route::post('/dashboard/packages/{investmentPackage}', function (Request $request, InvestmentPackage $investmentPackage) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:1'],
                'shares_count' => ['required', 'integer', 'min:1'],
                'units_limit' => ['required', 'integer', 'min:1'],
                'monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'display_order' => ['required', 'integer', 'min:1'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $investmentPackage->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'shares_count' => $validated['shares_count'],
                'units_limit' => $validated['units_limit'],
                'monthly_return_rate' => $validated['monthly_return_rate'],
                'display_order' => $validated['display_order'],
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('dashboard.packages')->with('packages_success', $investmentPackage->name.' updated successfully.');
        })->name('dashboard.packages.update');

        Route::post('/dashboard/packages/{investmentPackage}/archive', function (InvestmentPackage $investmentPackage) {
            $investmentPackage->forceFill(['is_active' => false])->save();

            return redirect()->route('dashboard.packages')->with('packages_success', $investmentPackage->name.' archived successfully.');
        })->name('dashboard.packages.archive');

        Route::post('/dashboard/packages/{investmentPackage}/delete', function (InvestmentPackage $investmentPackage) {
            if ($investmentPackage->investments()->exists()) {
                return redirect()->route('dashboard.packages')->with('packages_error', $investmentPackage->name.' cannot be deleted because it already has investment history. Archive it instead.');
            }

            $packageName = $investmentPackage->name;
            $investmentPackage->delete();

            return redirect()->route('dashboard.packages')->with('packages_success', $packageName.' deleted successfully.');
        })->name('dashboard.packages.delete');

        Route::get('/dashboard/miner', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $miners = MiningPlatform::activeMiners();
            $miner = MiningPlatform::resolveMiner($request->query('miner'));
            $miner->load([
                'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order'),
                'performanceLogs' => fn ($query) => $query->orderByDesc('logged_on')->limit(10),
            ]);

            $sharesSold = MiningPlatform::totalSharesSold($miner);
            $automaticSnapshot = MiningPlatform::performanceSnapshotForDate($miner);
            $latestLog = $miner->performanceLogs->first();
            $packageDailyCaps = $miner->packages
                ->map(fn ($package) => [
                    'package_id' => $package->id,
                    'name' => $package->name,
                    'price' => (float) $package->price,
                    'monthly_rate' => (float) $package->monthly_return_rate,
                    'daily_cap' => $package->price > 0
                        ? round(((float) $package->price * (float) $package->monthly_return_rate) / 30, 2)
                        : 0.0,
                ])
                ->values();

            return view('pages.general.miner', [
                'miners' => $miners,
                'miner' => $miner,
                'sharesSold' => $sharesSold,
                'availableShares' => max($miner->total_shares - $sharesSold, 0),
                'recentLogs' => $miner->performanceLogs,
                'automaticSnapshot' => $automaticSnapshot,
                'latestLog' => $latestLog,
                'packageDailyCaps' => $packageDailyCaps,
            ]);
        })->name('dashboard.miner');

        Route::post('/dashboard/miner', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'total_shares' => ['required', 'integer', 'min:1'],
                'share_price' => ['required', 'numeric', 'min:1'],
                'daily_output_usd' => ['required', 'numeric', 'min:0'],
                'monthly_output_usd' => ['required', 'numeric', 'min:0'],
                'base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'status' => ['required', 'in:active,paused,maintenance'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            $previousBaseMonthlyReturnRate = (float) $miner->base_monthly_return_rate;
            $validated['base_monthly_return_rate'] = round(((float) $validated['base_monthly_return_rate']) / 100, 4);
            unset($validated['miner_slug']);
            $miner->update($validated);

            $miner->packages()->get()->each(function (InvestmentPackage $package) use ($miner, $previousBaseMonthlyReturnRate) {
                if ((float) $package->price <= 0 || (int) $package->shares_count <= 0) {
                    $package->update(['monthly_return_rate' => 0]);

                    return;
                }

                $rateBonus = round((float) $package->monthly_return_rate - $previousBaseMonthlyReturnRate, 4);

                $package->update([
                    'monthly_return_rate' => max(0, round((float) $miner->base_monthly_return_rate + $rateBonus, 4)),
                ]);
            });

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('miner_success', $miner->name.' details have been updated successfully.');
        })->name('dashboard.miner.update');

        Route::post('/dashboard/miner/logs', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'logged_on' => ['required', 'date'],
                'revenue_usd' => ['required', 'numeric', 'min:0'],
                'electricity_cost_usd' => ['nullable', 'numeric', 'min:0'],
                'maintenance_cost_usd' => ['nullable', 'numeric', 'min:0'],
                'hashrate_th' => ['required', 'numeric', 'min:0'],
                'uptime_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'notes' => ['nullable', 'string'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            MiningPlatform::savePerformanceLog($miner, $validated, 'manual');

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('log_success', 'Performance log saved successfully for '.$miner->name.' and daily earnings were synced.');
        })->name('dashboard.miner.logs.store');

        Route::get('/dashboard/miner/logs/template', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $miner = MiningPlatform::resolveMiner($request->query('miner'));
            $filename = $miner->slug.'-performance-template.csv';

            return response()->streamDownload(function () use ($miner) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['logged_on', 'revenue_usd', 'electricity_cost_usd', 'maintenance_cost_usd', 'hashrate_th', 'uptime_percentage', 'notes']);
                fputcsv($handle, [
                    now()->toDateString(),
                    number_format((float) $miner->daily_output_usd, 2, '.', ''),
                    number_format((float) ((float) $miner->daily_output_usd * 0.18), 2, '.', ''),
                    number_format((float) ((float) $miner->daily_output_usd * 0.06), 2, '.', ''),
                    '500.00',
                    '99.50',
                    'Example imported performance row',
                ]);
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.miner.logs.template');

        Route::post('/dashboard/miner/logs/import', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            $handle = fopen($request->file('csv_file')->getRealPath(), 'r');

            if ($handle === false) {
                return redirect()
                    ->to(route('dashboard.miner').'?miner='.$miner->slug)
                    ->withErrors(['csv_file' => 'The CSV file could not be opened.']);
            }

            $header = fgetcsv($handle);
            if (! is_array($header) || $header === []) {
                fclose($handle);

                return redirect()
                    ->to(route('dashboard.miner').'?miner='.$miner->slug)
                    ->withErrors(['csv_file' => 'The CSV file must include a header row.']);
            }

            $normalizedHeader = collect($header)
                ->map(fn ($column) => Str::of((string) $column)->trim()->lower()->replace([' ', '-'], '_')->toString())
                ->values()
                ->all();

            $requiredColumns = ['logged_on', 'revenue_usd', 'hashrate_th', 'uptime_percentage'];
            $missingColumns = array_values(array_diff($requiredColumns, $normalizedHeader));

            if ($missingColumns !== []) {
                fclose($handle);

                return redirect()
                    ->to(route('dashboard.miner').'?miner='.$miner->slug)
                    ->withErrors(['csv_file' => 'Missing required CSV columns: '.implode(', ', $missingColumns)]);
            }

            $importedRows = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $mappedRow = collect($normalizedHeader)
                    ->combine(array_pad($row, count($normalizedHeader), null))
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                    ->all();

                MiningPlatform::savePerformanceLog($miner, [
                    'logged_on' => $mappedRow['logged_on'] ?? now()->toDateString(),
                    'revenue_usd' => (float) ($mappedRow['revenue_usd'] ?? 0),
                    'electricity_cost_usd' => isset($mappedRow['electricity_cost_usd']) && $mappedRow['electricity_cost_usd'] !== '' ? (float) $mappedRow['electricity_cost_usd'] : null,
                    'maintenance_cost_usd' => isset($mappedRow['maintenance_cost_usd']) && $mappedRow['maintenance_cost_usd'] !== '' ? (float) $mappedRow['maintenance_cost_usd'] : null,
                    'hashrate_th' => (float) ($mappedRow['hashrate_th'] ?? 0),
                    'uptime_percentage' => (float) ($mappedRow['uptime_percentage'] ?? 0),
                    'notes' => $mappedRow['notes'] ?? 'Imported from CSV upload.',
                ], 'manual');

                $importedRows++;
            }

            fclose($handle);

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('log_success', $importedRows.' CSV performance log row'.($importedRows === 1 ? ' was' : 's were').' imported for '.$miner->name.'.');
        })->name('dashboard.miner.logs.import');

        Route::post('/dashboard/miner/logs/copy-yesterday', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'logged_on' => ['nullable', 'date'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            $targetDate = Carbon::parse($validated['logged_on'] ?? now()->toDateString())->startOfDay();
            $yesterday = $targetDate->copy()->subDay()->toDateString();
            $sourceLog = $miner->performanceLogs()
                ->whereDate('logged_on', $yesterday)
                ->orderByRaw("CASE WHEN source = 'manual' THEN 0 ELSE 1 END")
                ->orderByDesc('updated_at')
                ->first();

            if (! $sourceLog) {
                $sourceLog = $miner->performanceLogs()
                    ->whereDate('logged_on', '<', $targetDate->toDateString())
                    ->orderByRaw("CASE WHEN source = 'manual' THEN 0 ELSE 1 END")
                    ->orderByDesc('logged_on')
                    ->orderByDesc('updated_at')
                    ->first();
            }

            if (! $sourceLog) {
                return redirect()
                    ->to(route('dashboard.miner').'?miner='.$miner->slug)
                    ->withErrors(['logged_on' => 'No previous performance log is available to copy yet.']);
            }

            MiningPlatform::savePerformanceLog($miner, [
                'logged_on' => $targetDate->toDateString(),
                'revenue_usd' => (float) $sourceLog->revenue_usd,
                'electricity_cost_usd' => (float) $sourceLog->electricity_cost_usd,
                'maintenance_cost_usd' => (float) $sourceLog->maintenance_cost_usd,
                'hashrate_th' => (float) $sourceLog->hashrate_th,
                'uptime_percentage' => (float) $sourceLog->uptime_percentage,
                'notes' => trim(($sourceLog->notes ? $sourceLog->notes.' ' : '').'Copied forward from '.$sourceLog->logged_on?->format('Y-m-d').'.'),
            ], 'manual');

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('log_success', 'Yesterday\'s performance was copied into '.$targetDate->format('M d, Y').' for '.$miner->name.'.');
        })->name('dashboard.miner.logs.copy-yesterday');

        Route::post('/dashboard/miner/logs/generate', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'logged_on' => ['nullable', 'date'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            $log = MiningPlatform::generateAutomaticPerformanceLog($miner, $validated['logged_on'] ?? now()->toDateString());

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('log_success', 'Automatic snapshot generated for '.$miner->name.' on '.$log->logged_on->format('M d, Y').'.');
        })->name('dashboard.miner.logs.generate');

        Route::get('/dashboard/mock-manager', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $miners = MiningPlatform::activeMiners();
            $miner = MiningPlatform::resolveMiner($request->query('miner'));
            $miner->load([
                'packages' => fn ($query) => $query->where('is_active', true)->where('price', '>', 0)->orderBy('display_order'),
            ]);

            $packages = $miner->packages->values();
            abort_if($packages->isEmpty(), 404, 'This miner has no active paid packages available for simulation.');

            $savedScenario = $request->filled('scenario')
                ? $request->user()->mockManagerScenarios()->with(['miner', 'package'])->find($request->integer('scenario'))
                : null;

            $selectedPackage = $packages->firstWhere('id', (int) ($savedScenario?->package_id ?? $request->query('package_id'))) ?? $packages->first();
            $automaticSnapshot = MiningPlatform::performanceSnapshotForDate($miner);
            $activeShares = max(MiningPlatform::totalSharesSold($miner), (int) $selectedPackage->shares_count);

            $defaultInputs = [
                'package_id' => $selectedPackage->id,
                'monthly_hashrate_th' => number_format((float) $automaticSnapshot['hashrate_th'], 2, '.', ''),
                'monthly_revenue_usd' => number_format((float) $miner->monthly_output_usd, 2, '.', ''),
                'monthly_electricity_cost_usd' => number_format((float) $miner->monthly_output_usd * 0.18, 2, '.', ''),
                'monthly_maintenance_cost_usd' => number_format((float) $miner->monthly_output_usd * 0.06, 2, '.', ''),
                'active_shares' => $activeShares,
                'verified_invites' => 20,
                'registered_referrals' => 5,
                'level_1_basic_subscribers' => $selectedPackage->price <= 100 ? 2 : 1,
                'level_1_growth_subscribers' => 1,
                'level_1_scale_subscribers' => 0,
                'level_2_basic_subscribers' => 1,
                'level_2_growth_subscribers' => 0,
                'level_2_scale_subscribers' => 0,
                'level_3_basic_subscribers' => 0,
                'level_3_growth_subscribers' => 0,
                'level_3_scale_subscribers' => 0,
                'level_4_basic_subscribers' => 0,
                'level_4_growth_subscribers' => 0,
                'level_4_scale_subscribers' => 0,
                'level_5_basic_subscribers' => 0,
                'level_5_growth_subscribers' => 0,
                'level_5_scale_subscribers' => 0,
            ];

            $inputs = array_replace($defaultInputs, $savedScenario?->inputs ?? []);
            $inputs['package_id'] = $selectedPackage->id;

            return view('pages.general.mock-manager', [
                'miners' => $miners,
                'miner' => $miner,
                'packages' => $packages,
                'selectedPackage' => $selectedPackage,
                'inputs' => $inputs,
                'scenario' => MiningPlatform::mockManagerScenario($miner, $selectedPackage, $inputs),
                'automaticSnapshot' => $automaticSnapshot,
                'savedScenarios' => $request->user()->mockManagerScenarios()->with(['miner', 'package'])->get(),
                'savedScenario' => $savedScenario,
            ]);
        })->name('dashboard.mock-manager');

        Route::post('/dashboard/mock-manager', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'package_id' => ['required', 'integer', 'exists:investment_packages,id'],
                'monthly_hashrate_th' => ['required', 'numeric', 'min:0'],
                'monthly_revenue_usd' => ['required', 'numeric', 'min:0'],
                'monthly_electricity_cost_usd' => ['required', 'numeric', 'min:0'],
                'monthly_maintenance_cost_usd' => ['required', 'numeric', 'min:0'],
                'active_shares' => ['required', 'integer', 'min:1'],
                'verified_invites' => ['required', 'integer', 'min:0'],
                'registered_referrals' => ['required', 'integer', 'min:0'],
                'level_1_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_1_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_1_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_scale_subscribers' => ['required', 'integer', 'min:0'],
            ]);

            $miners = MiningPlatform::activeMiners();
            $miner = MiningPlatform::resolveMiner($validated['miner_slug']);
            $miner->load([
                'packages' => fn ($query) => $query->where('is_active', true)->where('price', '>', 0)->orderBy('display_order'),
            ]);

            $packages = $miner->packages->values();
            $selectedPackage = $packages->firstWhere('id', (int) $validated['package_id']);

            abort_if(! $selectedPackage, 404, 'The selected package does not belong to this miner.');

            return view('pages.general.mock-manager', [
                'miners' => $miners,
                'miner' => $miner,
                'packages' => $packages,
                'selectedPackage' => $selectedPackage,
                'inputs' => $validated,
                'scenario' => MiningPlatform::mockManagerScenario($miner, $selectedPackage, $validated),
                'automaticSnapshot' => MiningPlatform::performanceSnapshotForDate($miner),
                'savedScenarios' => $request->user()->mockManagerScenarios()->with(['miner', 'package'])->get(),
                'savedScenario' => null,
            ]);
        })->name('dashboard.mock-manager.calculate');

        Route::post('/dashboard/mock-manager/save', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $validated = $request->validate([
                'scenario_name' => ['required', 'string', 'max:255'],
                'miner_slug' => ['required', 'string', 'exists:miners,slug'],
                'package_id' => ['required', 'integer', 'exists:investment_packages,id'],
                'monthly_hashrate_th' => ['required', 'numeric', 'min:0'],
                'monthly_revenue_usd' => ['required', 'numeric', 'min:0'],
                'monthly_electricity_cost_usd' => ['required', 'numeric', 'min:0'],
                'monthly_maintenance_cost_usd' => ['required', 'numeric', 'min:0'],
                'active_shares' => ['required', 'integer', 'min:1'],
                'verified_invites' => ['required', 'integer', 'min:0'],
                'registered_referrals' => ['required', 'integer', 'min:0'],
                'level_1_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_1_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_1_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_2_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_3_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_4_scale_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_basic_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_growth_subscribers' => ['required', 'integer', 'min:0'],
                'level_5_scale_subscribers' => ['required', 'integer', 'min:0'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            abort_unless(
                $miner->packages()->whereKey($validated['package_id'])->exists(),
                404,
                'The selected package does not belong to this miner.'
            );

            $scenario = MockManagerScenario::create([
                'user_id' => $request->user()->id,
                'miner_id' => $miner->id,
                'package_id' => $validated['package_id'],
                'name' => $validated['scenario_name'],
                'inputs' => collect($validated)->except('scenario_name', 'miner_slug')->all(),
            ]);

            return redirect()
                ->route('dashboard.mock-manager', ['miner' => $miner->slug, 'scenario' => $scenario->id])
                ->with('mock_manager_success', 'Scenario "'.$scenario->name.'" was saved.');
        })->name('dashboard.mock-manager.save');

        Route::post('/dashboard/mock-manager/{mockManagerScenario}/delete', function (Request $request, MockManagerScenario $mockManagerScenario) {
            abort_unless($mockManagerScenario->user_id === $request->user()->id, 403);

            $minerSlug = $mockManagerScenario->miner?->slug;
            $scenarioName = $mockManagerScenario->name;
            $mockManagerScenario->delete();

            return redirect()
                ->route('dashboard.mock-manager', $minerSlug ? ['miner' => $minerSlug] : [])
                ->with('mock_manager_success', 'Scenario "'.$scenarioName.'" was deleted.');
        })->name('dashboard.mock-manager.delete');
    });

    Route::get('/dashboard/friends', function () {
        $friendInvitationDailyEmailLimit = 10;
        $user = request()->user();
        $friendInvitationEmailsSentToday = optional($user->friend_invitation_emails_sent_on)->isToday()
            ? (int) ($user->friend_invitation_emails_sent_count ?? 0)
            : 0;
        $friendInvitationCountries = [
            'Australia',
            'Bahrain',
            'Canada',
            'Egypt',
            'France',
            'Germany',
            'India',
            'Iraq',
            'Jordan',
            'Kuwait',
            'Lebanon',
            'Oman',
            'Qatar',
            'Saudi Arabia',
            'Turkey',
            'United Arab Emirates',
            'United Kingdom',
            'United States',
        ];

        return view('pages.general.friends', [
            'user' => $user,
            'friendInvitations' => $user->friendInvitations,
            'friendInvitationCountries' => $friendInvitationCountries,
            'friendInvitationDailyEmailLimit' => $friendInvitationDailyEmailLimit,
            'friendInvitationEmailsRemaining' => max($friendInvitationDailyEmailLimit - $friendInvitationEmailsSentToday, 0),
        ]);
    })->name('dashboard.friends');

    Route::post('/dashboard/friends/invite', function (Request $request) {
        $friendInvitationDailyEmailLimit = 10;
        $friendInvitationEmailsSentToday = optional($request->user()->friend_invitation_emails_sent_on)->isToday()
            ? (int) ($request->user()->friend_invitation_emails_sent_count ?? 0)
            : 0;
        $friendInvitationCountries = [
            'Australia',
            'Bahrain',
            'Canada',
            'Egypt',
            'France',
            'Germany',
            'India',
            'Iraq',
            'Jordan',
            'Kuwait',
            'Lebanon',
            'Oman',
            'Qatar',
            'Saudi Arabia',
            'Turkey',
            'United Arab Emirates',
            'United Kingdom',
            'United States',
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', Rule::in($friendInvitationCountries)],
        ]);

        if ($friendInvitationEmailsSentToday >= $friendInvitationDailyEmailLimit) {
            return redirect()
                ->route('dashboard.friends')
                ->with('invite_limit', 'Daily invitation email limit reached. You can send up to '.$friendInvitationDailyEmailLimit.' invitation emails per day.');
        }

        $friendInvitation = FriendInvitation::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'email' => $validated['email'],
            ],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'] ?? null,
            ],
        );

        $verificationUrl = URL::temporarySignedRoute(
            'friend-invitations.verify',
            now()->addDays(7),
            ['friendInvitation' => $friendInvitation->id],
        );

        Mail::to($friendInvitation->email)->send(
            new FriendInvitationMail($friendInvitation, $request->user(), $verificationUrl)
        );

        $request->user()->forceFill([
            'friend_invitation_emails_sent_on' => today(),
            'friend_invitation_emails_sent_count' => $friendInvitationEmailsSentToday + 1,
        ])->save();

        return redirect()
            ->route('dashboard.friends')
            ->with('invite_success', $validated['name'].' has been invited successfully and the email has been sent.');
    })->name('dashboard.friends.invite');

    Route::post('/dashboard/friends/{friendInvitation}/resend', function (Request $request, FriendInvitation $friendInvitation) {
        $friendInvitationDailyEmailLimit = 10;
        $friendInvitationEmailsSentToday = optional($request->user()->friend_invitation_emails_sent_on)->isToday()
            ? (int) ($request->user()->friend_invitation_emails_sent_count ?? 0)
            : 0;
        abort_unless($friendInvitation->user_id === $request->user()->id, 403);

        if ($friendInvitation->verified_at) {
            return redirect()->route('dashboard.friends')->with('invite_success', $friendInvitation->name.' is already verified, so no resend was needed.');
        }

        if ($friendInvitationEmailsSentToday >= $friendInvitationDailyEmailLimit) {
            return redirect()
                ->route('dashboard.friends')
                ->with('invite_limit', 'Daily invitation email limit reached. You can send up to '.$friendInvitationDailyEmailLimit.' invitation emails per day.');
        }

        $verificationUrl = URL::temporarySignedRoute(
            'friend-invitations.verify',
            now()->addDays(7),
            ['friendInvitation' => $friendInvitation->id],
        );

        Mail::to($friendInvitation->email)->send(
            new FriendInvitationMail($friendInvitation, $request->user(), $verificationUrl)
        );

        $request->user()->forceFill([
            'friend_invitation_emails_sent_on' => today(),
            'friend_invitation_emails_sent_count' => $friendInvitationEmailsSentToday + 1,
        ])->save();

        return redirect()
            ->route('dashboard.friends')
            ->with('invite_success', 'Invitation email resent to '.$friendInvitation->email.'.');
    })->name('dashboard.friends.resend');

    Route::get('/profile/photo/{user}', function (User $user) {
        abort_unless($user->profile_photo_path, 404);
        abort_unless(Storage::disk('public')->exists($user->profile_photo_path), 404);

        return Storage::disk('public')->response($user->profile_photo_path);
    })->name('profile.photo');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/admin-two-factor/setup', [AdminTwoFactorController::class, 'store'])
        ->middleware(['admin', 'throttle:6,1'])
        ->name('profile.admin-two-factor.store');
    Route::post('/profile/admin-two-factor/confirm', [AdminTwoFactorController::class, 'confirm'])
        ->middleware(['admin', 'throttle:6,1'])
        ->name('profile.admin-two-factor.confirm');
    Route::delete('/profile/admin-two-factor', [AdminTwoFactorController::class, 'destroy'])
        ->middleware(['admin', 'throttle:6,1'])
        ->name('profile.admin-two-factor.destroy');


    Route::group(['prefix' => 'apps'], function () {
        Route::view('chat', 'pages.apps.chat');
        Route::view('calendar', 'pages.apps.calendar');
    });

    Route::group(['prefix' => 'ui-components'], function () {
        Route::view('accordion', 'pages.ui-components.accordion');
        Route::view('alerts', 'pages.ui-components.alerts');
        Route::view('badges', 'pages.ui-components.badges');
        Route::view('breadcrumbs', 'pages.ui-components.breadcrumbs');
        Route::view('buttons', 'pages.ui-components.buttons');
        Route::view('button-group', 'pages.ui-components.button-group');
        Route::view('cards', 'pages.ui-components.cards');
        Route::view('carousel', 'pages.ui-components.carousel');
        Route::view('collapse', 'pages.ui-components.collapse');
        Route::view('dropdowns', 'pages.ui-components.dropdowns');
        Route::view('list-group', 'pages.ui-components.list-group');
        Route::view('media-object', 'pages.ui-components.media-object');
        Route::view('modal', 'pages.ui-components.modal');
        Route::view('navs', 'pages.ui-components.navs');
        Route::view('offcanvas', 'pages.ui-components.offcanvas');
        Route::view('pagination', 'pages.ui-components.pagination');
        Route::view('placeholders', 'pages.ui-components.placeholders');
        Route::view('popovers', 'pages.ui-components.popovers');
        Route::view('progress', 'pages.ui-components.progress');
        Route::view('scrollbar', 'pages.ui-components.scrollbar');
        Route::view('scrollspy', 'pages.ui-components.scrollspy');
        Route::view('spinners', 'pages.ui-components.spinners');
        Route::view('tabs', 'pages.ui-components.tabs');
        Route::view('toasts', 'pages.ui-components.toasts');
        Route::view('tooltips', 'pages.ui-components.tooltips');
    });

    Route::group(['prefix' => 'advanced-ui'], function () {
        Route::view('cropper', 'pages.advanced-ui.cropper');
        Route::view('owl-carousel', 'pages.advanced-ui.owl-carousel');
        Route::view('sortablejs', 'pages.advanced-ui.sortablejs');
        Route::view('sweet-alert', 'pages.advanced-ui.sweet-alert');
    });

    Route::group(['prefix' => 'forms'], function () {
        Route::view('basic-elements', 'pages.forms.basic-elements');
        Route::view('advanced-elements', 'pages.forms.advanced-elements');
        Route::view('editors', 'pages.forms.editors');
        Route::view('wizard', 'pages.forms.wizard');
    });

    Route::group(['prefix' => 'charts'], function () {
        Route::view('apex', 'pages.charts.apex');
        Route::view('chartjs', 'pages.charts.chartjs');
        Route::view('flot', 'pages.charts.flot');
        Route::view('peity', 'pages.charts.peity');
        Route::view('sparkline', 'pages.charts.sparkline');
    });

    Route::group(['prefix' => 'tables'], function () {
        Route::view('basic-tables', 'pages.tables.basic-tables');
        Route::view('data-table', 'pages.tables.data-table');
    });

    Route::group(['prefix' => 'icons'], function () {
        Route::view('lucide-icons', 'pages.icons.lucide-icons');
        Route::view('flag-icons', 'pages.icons.flag-icons');
        Route::view('mdi-icons', 'pages.icons.mdi-icons');
    });

    Route::get('/dashboard/buy-shares', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load(['shareholder', 'userLevel', 'investments.package', 'investments.miner']);
        $level = MiningPlatform::syncUserLevel($user);
        $user->load(['shareholder', 'userLevel', 'investments.package', 'investments.miner']);

        $miners = MiningPlatform::activeMiners();
        $miner = MiningPlatform::resolveMiner($request->query('miner'));
        $miner->load(['packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')]);

        $sharesSold = MiningPlatform::totalSharesSold($miner);

        $qrWriter = new SvgWriter();

        $paymentMethods = collect([
            [
                'key' => 'btc_transfer',
                'label' => MiningPlatform::platformSetting('payment_btc_transfer_label'),
                'enabled' => MiningPlatform::platformSetting('payment_btc_transfer_enabled') === '1',
                'destination' => MiningPlatform::platformSetting('payment_btc_transfer_destination'),
                'reference_hint' => MiningPlatform::platformSetting('payment_btc_transfer_reference_hint'),
                'instruction' => MiningPlatform::platformSetting('payment_btc_transfer_instruction'),
            ],
            [
                'key' => 'usdt_transfer',
                'label' => MiningPlatform::platformSetting('payment_usdt_transfer_label'),
                'enabled' => MiningPlatform::platformSetting('payment_usdt_transfer_enabled') === '1',
                'destination' => MiningPlatform::platformSetting('payment_usdt_transfer_destination'),
                'reference_hint' => MiningPlatform::platformSetting('payment_usdt_transfer_reference_hint'),
                'instruction' => MiningPlatform::platformSetting('payment_usdt_transfer_instruction'),
            ],
            [
                'key' => 'bank_transfer',
                'label' => MiningPlatform::platformSetting('payment_bank_transfer_label'),
                'enabled' => MiningPlatform::platformSetting('payment_bank_transfer_enabled') === '1',
                'destination' => MiningPlatform::platformSetting('payment_bank_transfer_destination'),
                'reference_hint' => MiningPlatform::platformSetting('payment_bank_transfer_reference_hint'),
                'instruction' => MiningPlatform::platformSetting('payment_bank_transfer_instruction'),
            ],
        ])->map(function (array $method) use ($qrWriter) {
            $destination = trim((string) ($method['destination'] ?? ''));
            $method['qr_code_data_uri'] = null;

            if (in_array($method['key'], ['btc_transfer', 'usdt_transfer'], true) && '' !== $destination) {
                $method['qr_code_data_uri'] = $qrWriter->write(new QrCode(
                    data: $destination,
                    size: 160,
                    margin: 8,
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                ))->getDataUri();
            }

            return $method;
        })->filter(fn (array $method) => $method['enabled'])->values();
        return view('pages.general.buy-shares', [
            'user' => $user,
            'level' => $level,
            'miners' => $miners,
            'miner' => $miner,
            'packages' => $miner->packages,
            'shareholder' => $user->shareholder,
            'activeInvestment' => $user->investments->where('status', 'active')->firstWhere('miner_id', $miner->id),
            'pendingInvestmentOrder' => InvestmentOrder::query()->where('user_id', $user->id)->where('miner_id', $miner->id)->where('status', 'pending')->latest('submitted_at')->first(),
            'rejectedInvestmentOrder' => InvestmentOrder::query()->where('user_id', $user->id)->where('miner_id', $miner->id)->where('status', 'rejected')->latest('rejected_at')->first(),
            'sharesSold' => $sharesSold,
            'paymentMethods' => $paymentMethods,
            'availableShares' => max($miner->total_shares - $sharesSold, 0),
        ]);
    })->name('dashboard.buy-shares');

    Route::post('/dashboard/buy-shares/subscribe', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $paymentMethodKeys = collect([
            MiningPlatform::platformSetting('payment_btc_transfer_enabled') === '1' ? 'btc_transfer' : null,
            MiningPlatform::platformSetting('payment_usdt_transfer_enabled') === '1' ? 'usdt_transfer' : null,
            MiningPlatform::platformSetting('payment_bank_transfer_enabled') === '1' ? 'bank_transfer' : null,
        ])->filter()->values()->all();

        $validated = $request->validate([
            'package' => ['required', 'string', 'exists:investment_packages,slug'],
            'payment_method' => ['required', 'string', Rule::in($paymentMethodKeys)],
            'payment_reference' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $package = InvestmentPackage::with('miner')
            ->where('slug', $validated['package'])
            ->where('is_active', true)
            ->firstOrFail();

        $existingPendingOrder = InvestmentOrder::query()
            ->where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPendingOrder) {
            return redirect()
                ->route('dashboard.buy-shares', ['miner' => $package->miner?->slug])
                ->with('subscription_success', 'You already have a pending payment review for the '.$package->name.' package.');
        }

        $investmentOrder = MiningPlatform::submitInvestmentOrder($user, $package, $validated);
        $submittedTemplate = MiningPlatform::activityTemplate('investment_payment_submitted', [
            'package_name' => $package->name,
        ]);

        $user->notify(new ActivityFeedNotification([
            'category' => 'investment',
            'status' => 'info',
            'subject' => $submittedTemplate['subject'],
            'message' => $submittedTemplate['message'],
            'context_label' => 'Payment reference',
            'context_value' => $investmentOrder->payment_reference,
            'amount' => (float) $investmentOrder->amount,
            'amount_label' => 'Submitted amount',
            'force_mail' => true,
        ]));

        return redirect()
            ->route('dashboard.buy-shares', ['miner' => $package->miner?->slug])
            ->with('subscription_success', 'Your payment for '.$package->name.' has been submitted. Upload the payment proof after you complete the transfer.');
    })->middleware('throttle:8,1')->name('dashboard.buy-shares.subscribe');

    Route::post('/dashboard/buy-shares/{investmentOrder}/proof', function (Request $request, InvestmentOrder $investmentOrder) {
        MiningPlatform::ensureDefaults();

        abort_unless($investmentOrder->user_id === $request->user()->id, 403);
        abort_unless(in_array($investmentOrder->status, ['pending', 'rejected'], true), 403);

        $validated = $request->validate([
            'payment_proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
                new AllowedFileSignature([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                ]),
            ],
        ]);

        if ($investmentOrder->payment_proof_path) {
            Storage::disk('public')->delete($investmentOrder->payment_proof_path);
        }

        $storedPath = $validated['payment_proof']->store('investment-proofs', 'public');

        $investmentOrder->forceFill([
            'payment_proof_path' => $storedPath,
            'payment_proof_original_name' => $validated['payment_proof']->getClientOriginalName(),
            'proof_uploaded_at' => now(),
        ])->save();

        $proofTemplate = MiningPlatform::activityTemplate('investment_payment_proof', [
            'package_name' => $investmentOrder->package?->name ?? 'your pending investment',
        ]);

        $investmentOrder->user?->notify(new ActivityFeedNotification([
            'category' => 'investment',
            'status' => 'info',
            'subject' => $proofTemplate['subject'],
            'message' => $proofTemplate['message'],
            'context_label' => 'Uploaded file',
            'context_value' => $investmentOrder->payment_proof_original_name ?? 'Payment proof',
            'amount' => (float) $investmentOrder->amount,
            'amount_label' => 'Submitted amount',
            'force_mail' => true,
        ]));

        return redirect()
            ->route('dashboard.buy-shares', ['miner' => $investmentOrder->miner?->slug])
            ->with('subscription_success', 'Payment proof uploaded successfully. The admin team can review it now.');
    })->middleware('throttle:6,1')->name('dashboard.buy-shares.proof');

    Route::get('/investment-orders/{investmentOrder}/proof-file', function (Request $request, InvestmentOrder $investmentOrder) {
        abort_unless($investmentOrder->payment_proof_path, 404);
        abort_unless($request->user()->id === $investmentOrder->user_id || $request->user()->isAdmin(), 403);

        return Storage::disk('public')->response($investmentOrder->payment_proof_path, $investmentOrder->payment_proof_original_name ?? basename($investmentOrder->payment_proof_path));
    })->name('investment-orders.proof-file');

    Route::group(['prefix' => 'general'], function () {
        Route::view('blank-page', 'pages.general.blank-page');
        Route::view('faq', 'pages.general.faq');
        Route::view('invoice', 'pages.general.invoice');
        Route::redirect('profile', '/dashboard/profile');
        Route::view('pricing', 'pages.general.pricing');
        Route::view('timeline', 'pages.general.timeline');
    });

    Route::group(['prefix' => 'error'], function () {
        Route::view('404', 'pages.error.404');
        Route::view('500', 'pages.error.500');
    });
});

require __DIR__.'/auth.php';











































Route::redirect('/general/sell-products', '/dashboard/buy-shares');































