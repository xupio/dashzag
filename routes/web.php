<?php

use App\Http\Controllers\InternalMailController;
use App\Http\Controllers\ProfileController;
use App\Mail\FriendInvitationMail;
use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentOrder;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\PayoutRequest;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
use App\Models\User;
use App\Models\UserInvestment;
use App\Notifications\ActivityFeedNotification;
use App\Notifications\DigestSummaryNotification;
use App\Notifications\PayoutStatusNotification;
use App\Support\MiningPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

Route::get('/packages', function () use ($marketingPageData) {
    return view('marketing.packages', $marketingPageData());
})->name('marketing.packages');

Route::get('/media', function () use ($marketingPageData) {
    return view('marketing.media', $marketingPageData());
})->name('marketing.media');

Route::get('/references', function () use ($marketingPageData) {
    return view('marketing.references', $marketingPageData());
})->name('marketing.references');

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

    $user = $request->user()->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations']);
    $level = MiningPlatform::syncUserLevel($user);
    $user->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations']);

    $miners = MiningPlatform::activeMiners();
    $miner = MiningPlatform::resolveMiner($request->query('miner'));
    $miner->load(['performanceLogs', 'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')]);

    $performanceLogs = $miner->performanceLogs()->orderBy('logged_on')->take(7)->get();
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
        ->with(['user.userLevel', 'package'])
        ->where('miner_id', $miner->id)
        ->where('status', 'active')
        ->orderByDesc('subscribed_at')
        ->get()
        ->groupBy('user_id')
        ->map(function ($investments) {
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
            ];
        })
        ->filter(fn ($row) => $row['user'])
        ->sortByDesc(fn ($row) => optional($row['latest_subscribed_at'])->timestamp ?? 0)
        ->values();

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
        'performanceHashrateData' => $performanceLogs->map(fn ($log) => round((float) $log->hashrate_th, 2))->values(),
        'performanceUptimeData' => $performanceLogs->map(fn ($log) => round((float) $log->uptime_percentage, 2))->values(),
        'recentPerformanceLogs' => $recentPerformanceLogs,
        'shareStatusLabels' => $shareStatusBreakdown->pluck('label')->values()->all(),
        'shareStatusSeries' => $shareStatusBreakdown->pluck('shares')->values()->all(),
        'shareStatusDetails' => $shareStatusBreakdown->values()->all(),
        'minerInvestorPipeline' => $minerInvestorPipeline,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/email/inbox', [InternalMailController::class, 'inbox'])->name('email.inbox');
    Route::get('/email/starred', [InternalMailController::class, 'starred'])->name('email.starred');
    Route::get('/email/archived', [InternalMailController::class, 'archived'])->name('email.archived');
    Route::get('/email/sent', [InternalMailController::class, 'sent'])->name('email.sent');
    Route::get('/email/compose', [InternalMailController::class, 'compose'])->name('email.compose');
    Route::post('/email/send', [InternalMailController::class, 'store'])->name('email.store');
    Route::post('/email/{message}/reply', [InternalMailController::class, 'reply'])->name('email.reply');
    Route::get('/email/inbox/{recipient}/read', [InternalMailController::class, 'showInbox'])->name('email.read');
    Route::post('/email/inbox/{recipient}/toggle-star', [InternalMailController::class, 'toggleStar'])->name('email.toggle-star');
    Route::post('/email/inbox/{recipient}/toggle-read', [InternalMailController::class, 'toggleRead'])->name('email.toggle-read');
    Route::post('/email/inbox/{recipient}/archive', [InternalMailController::class, 'archive'])->name('email.archive');
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
        $availableEarnings = (float) $user->earnings->where('status', 'available')->sum('amount');
        $pendingReferrals = $user->friendInvitations->whereNull('verified_at')->count();
        $verifiedReferrals = $user->friendInvitations->whereNotNull('verified_at')->count();
        $registeredReferrals = $user->friendInvitations->whereNotNull('registered_at')->count();
        $teamBonusRate = MiningPlatform::teamBonusRate($user);
        $investmentAllocation = $activeInvestments
            ->groupBy(fn ($investment) => $investment->miner?->name ?? 'Unknown miner')
            ->map(fn ($investments) => round((float) $investments->sum('amount'), 2))
            ->sortDesc();

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
        $investmentAllocation = $activeInvestments
            ->groupBy(fn ($investment) => $investment->miner?->name ?? 'Unknown miner')
            ->map(fn ($investments) => round((float) $investments->sum('amount'), 2))
            ->sortDesc();

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
        $filteredNotifications = $filter === 'all'
            ? $notifications
            : $notifications->filter(
                fn ($notification) => ($notification->data['category'] ?? 'payout') === $filter
            )->values();

        return view('pages.general.notifications', [
            'notifications' => $filteredNotifications,
            'allNotificationsCount' => $notifications->count(),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'activeFilter' => $filter,
            'notificationFilters' => [
                'all' => 'All',
                'payout' => 'Payouts',
                'reward' => 'Rewards',
                'investment' => 'Investments',
                'network' => 'Network',
                'milestone' => 'Milestones',
                'digest' => 'Digests',
            ],
        ]);
    })->name('dashboard.notifications');

    Route::post('/dashboard/notifications/read-all', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('dashboard.notifications')->with('notifications_success', 'All notifications have been marked as read.');
    })->name('dashboard.notifications.read-all');

    Route::post('/dashboard/notifications/{notification}/read', function (Request $request, string $notification) {
        $dashboardNotification = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $dashboardNotification->markAsRead();

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

        $user = request()->user()->load(['investments.miner', 'investments.package', 'earnings']);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $totalInvested = (float) $activeInvestments->sum('amount');
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
        $availableEarnings = (float) $user->earnings->where('status', 'available')->sum('amount');

        return view('pages.general.investments', [
            'user' => $user,
            'investments' => $user->investments,
            'activeInvestments' => $activeInvestments,
            'totalInvested' => $totalInvested,
            'totalSharesOwned' => (int) $activeInvestments->sum('shares_owned'),
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
            'availableEarnings' => $availableEarnings,
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

        $user = request()->user()->load([
            'sponsor',
            'friendInvitations',
            'earnings',
            'referralEvents.relatedUser',
            'referralEvents.investment.package',
            'sponsoredUsers' => fn ($query) => $query->with([
                'investments.package',
                'investments.miner',
                'sponsoredUsers.investments.package',
                'sponsoredUsers.investments.miner',
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

        $directTeamBranches = $directTeam->map(function ($member) {
            $activeInvestments = $member->investments->where('status', 'active')->where('amount', '>', 0);
            $branchSecondLevel = $member->sponsoredUsers->values();
            $branchSecondLevelActive = $branchSecondLevel->filter(fn ($downline) => $downline->investments->where('status', 'active')->where('amount', '>', 0)->isNotEmpty());

            return [
                'member' => $member,
                'active_investments' => $activeInvestments,
                'active_capital' => (float) $activeInvestments->sum('amount'),
                'active_package' => $activeInvestments->sortByDesc('subscribed_at')->first()?->package?->name,
                'is_active_investor' => $activeInvestments->isNotEmpty(),
                'downline_count' => $branchSecondLevel->count(),
                'downline_active_count' => $branchSecondLevelActive->count(),
                'downline_capital' => (float) $branchSecondLevel->sum(fn ($downline) => $downline->investments->where('status', 'active')->where('amount', '>', 0)->sum('amount')),
                'downline_members' => $branchSecondLevel,
            ];
        })->values();

        $referralRewards = $user->earnings
            ->whereIn('source', ['referral_registration', 'referral_subscription', 'team_subscription_bonus', 'team_downline_bonus'])
            ->sortByDesc(fn ($reward) => optional($reward->earned_on)->timestamp ?? 0)
            ->values();

        $activeTeamInvestors = $directTeam->filter(fn ($member) => $member->investments->where('status', 'active')->isNotEmpty())->count();
        $teamCapital = (float) $directTeam->sum(fn ($member) => $member->investments->where('status', 'active')->sum('amount'));
        $teamBonusRate = MiningPlatform::teamBonusRate($user);

        return view('pages.general.network', [
            'user' => $user,
            'friendInvitations' => $friendInvitations,
            'directTeam' => $directTeam,
            'directTeamBranches' => $directTeamBranches,
            'secondLevelTeam' => $secondLevelTeam,
            'teamEvents' => $user->referralEvents->sortByDesc('created_at')->values(),
            'teamBonusRate' => $teamBonusRate,
            'activeTeamInvestors' => $activeTeamInvestors,
            'teamCapital' => $teamCapital,
            'invitedCount' => $friendInvitations->count(),
            'verifiedCount' => $friendInvitations->whereNotNull('verified_at')->count(),
            'registeredCount' => $friendInvitations->whereNotNull('registered_at')->count(),
            'subscribedCount' => $activeInvestorEmails->count(),
            'activeInvestorEmails' => $activeInvestorEmails,
            'referralRewards' => $referralRewards,
            'referralRewardsTotal' => (float) $referralRewards->sum('amount'),
        ]);
    })->name('dashboard.network');

    Route::get('/dashboard/wallet', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['userLevel', 'earnings.investment.package', 'investments.package', 'payoutRequests']);
        $wallet = MiningPlatform::walletSummary($user);
        $activeInvestments = $user->investments->where('status', 'active')->values();
        $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);

        return view('pages.general.wallet', [
            'user' => $user,
            'wallet' => $wallet,
            'earnings' => $user->earnings,
            'payoutRequests' => $user->payoutRequests,
            'activeInvestments' => $activeInvestments,
            'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
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
            return back()->withErrors(['amount' => 'Requested amount exceeds available wallet balance.'])->withInput();
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
    })->name('dashboard.wallet.request');

    Route::post('/dashboard/wallet/generate', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['investments']);
        $generated = MiningPlatform::generateMonthlyEarnings($user);

        return redirect()
            ->route('dashboard.wallet')
            ->with('wallet_success', $generated->count().' monthly earning entries are now available in your wallet.');
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
        Route::get('/dashboard/analytics', function () {
            MiningPlatform::ensureDefaults();

            $users = User::with(['friendInvitations', 'investments', 'earnings'])->get();
            $packages = InvestmentPackage::with('investments')->orderBy('display_order')->get();
            $miner = MiningPlatform::resolveMiner(request()->query('miner'));
            $miners = Miner::with(['investments.user', 'packages'])->orderBy('name')->get();

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
                'miner' => $miner,
                'miners' => $miners,
            ]);
        })->name('dashboard.analytics');

        Route::get('/dashboard/analytics/export', function () {
            MiningPlatform::ensureDefaults();

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

            return response()->streamDownload(function () use ($mlmRewardBreakdown) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Section', 'Label', 'Value']);
                fputcsv($handle, ['Summary', 'Total invested', number_format((float) UserInvestment::where('status', 'active')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Shares sold', (int) UserInvestment::where('status', 'active')->sum('shares_owned')]);
                fputcsv($handle, ['Summary', 'Available liability', number_format((float) Earning::where('status', 'available')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Pending payouts', number_format((float) PayoutRequest::whereIn('status', ['pending', 'approved'])->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Paid out', number_format((float) Earning::where('status', 'paid')->sum('amount'), 2, '.', '')]);
                fputcsv($handle, ['Summary', 'Active shareholders', (int) User::where('account_type', 'shareholder')->count()]);

                foreach ($mlmRewardBreakdown as $rewardLevel) {
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' source', $rewardLevel['source']]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' entries', $rewardLevel['count']]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' available', number_format($rewardLevel['available_total'], 2, '.', '')]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' paid', number_format($rewardLevel['paid_total'], 2, '.', '')]);
                    fputcsv($handle, ['MLM payout breakdown', 'Level '.$rewardLevel['level'].' total', number_format($rewardLevel['overall_total'], 2, '.', '')]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.analytics.export');

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
            $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'admin_manual', $request->user()->id, $request->user()->name));

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
                $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'admin_bulk', $request->user()->id, $request->user()->name));

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
                        $entry['data']['triggered_by_name'] ?? 'System',
                        $entry['data']['period_label'] ?? 'Last activity window',
                        $entry['data']['amount'] ?? 0,
                        optional($entry['notification']->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.digests.history.export');

        Route::get('/dashboard/network-admin', function () {
            MiningPlatform::ensureDefaults();

            $users = User::with([
                'sponsor',
                'userLevel',
                'investments.package',
                'investments.miner',
                'sponsoredUsers.investments',
            ])->orderBy('name')->get();

            return view('pages.general.network-admin', [
                'users' => $users,
                'events' => ReferralEvent::with(['sponsor', 'relatedUser', 'investment.package'])
                    ->latest()
                    ->limit(25)
                    ->get(),
            ]);
        })->name('dashboard.network-admin');

        Route::get('/dashboard/shareholders', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $minerSlug = $request->query('miner');
            $status = $request->query('status');

            $investments = UserInvestment::with(['user', 'miner', 'package'])
                ->when($minerSlug, fn ($query) => $query->whereHas('miner', fn ($minerQuery) => $minerQuery->where('slug', $minerSlug)))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest('subscribed_at')
                ->get();

            return view('pages.general.shareholders', [
                'miners' => Miner::orderBy('name')->get(),
                'investments' => $investments,
                'selectedMiner' => $minerSlug,
                'selectedStatus' => $status,
            ]);
        })->name('dashboard.shareholders');

        Route::get('/dashboard/shareholders/export', function (Request $request) {
            MiningPlatform::ensureDefaults();

            $minerSlug = $request->query('miner');
            $status = $request->query('status');

            $investments = UserInvestment::with(['user', 'miner', 'package'])
                ->when($minerSlug, fn ($query) => $query->whereHas('miner', fn ($minerQuery) => $minerQuery->where('slug', $minerSlug)))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest('subscribed_at')
                ->get();

            $filename = 'shareholders-report-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($investments) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Investor Name', 'Investor Email', 'Miner', 'Package', 'Amount', 'Shares', 'Return Rate', 'Status', 'Subscribed At']);

                foreach ($investments as $investment) {
                    fputcsv($handle, [
                        $investment->user?->name,
                        $investment->user?->email,
                        $investment->miner?->name,
                        $investment->package?->name,
                        number_format((float) $investment->amount, 2, '.', ''),
                        (int) $investment->shares_owned,
                        number_format(((float) $investment->monthly_return_rate + (float) $investment->level_bonus_rate) * 100, 2, '.', '').'%',
                        $investment->status,
                        optional($investment->subscribed_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        })->name('dashboard.shareholders.export');
        Route::get('/dashboard/users', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.users', [
                'users' => User::with(['userLevel', 'investments', 'earnings'])->orderBy('created_at')->get(),
            ]);
        })->name('dashboard.users');

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
            ]);
        })->name('dashboard.operations');

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

            return response()->streamDownload(function () use ($investmentOrders) {
                $handle = fopen('php://output', 'w');
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
                        $order->approver?->name,
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
            $payoutRequest->user?->notify(new PayoutStatusNotification($payoutRequest, 'approved'));

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request approved successfully.');
        })->name('dashboard.operations.payouts.approve');

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

            $payoutRequest->user?->notify(new PayoutStatusNotification($payoutRequest, 'paid'));

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request marked as paid.');
        })->name('dashboard.operations.payouts.pay');

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
        })->name('dashboard.operations.investment-orders.approve');
        Route::post('/dashboard/operations/investment-orders/{investmentOrder}/reject', function (Request $request, InvestmentOrder $investmentOrder) {
            $validated = $request->validate([
                'admin_notes' => ['required', 'string', 'max:1000'],
            ]);

            MiningPlatform::rejectInvestmentOrder($investmentOrder, $request->user(), $validated['admin_notes']);

            return redirect()->route('dashboard.operations')->with('operations_success', 'Investment order rejected successfully.');
        })->name('dashboard.operations.investment-orders.reject');

        Route::get('/dashboard/rewards', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.rewards', [
                'settings' => MiningPlatform::rewardSettings(),
            ]);
        })->name('dashboard.rewards');

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

            return view('pages.general.miner', [
                'miners' => $miners,
                'miner' => $miner,
                'sharesSold' => $sharesSold,
                'availableShares' => max($miner->total_shares - $sharesSold, 0),
                'recentLogs' => $miner->performanceLogs,
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
                'hashrate_th' => ['required', 'numeric', 'min:0'],
                'uptime_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'notes' => ['nullable', 'string'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();

            MinerPerformanceLog::updateOrCreate(
                [
                    'miner_id' => $miner->id,
                    'logged_on' => $validated['logged_on'],
                ],
                [
                    'revenue_usd' => $validated['revenue_usd'],
                    'hashrate_th' => $validated['hashrate_th'],
                    'uptime_percentage' => $validated['uptime_percentage'],
                    'notes' => $validated['notes'] ?? null,
                ],
            );

            return redirect()
                ->to(route('dashboard.miner').'?miner='.$miner->slug)
                ->with('log_success', 'Performance log saved successfully for '.$miner->name.'.');
        })->name('dashboard.miner.logs.store');
    });

    Route::get('/dashboard/friends', function () {
        $user = request()->user();

        return view('pages.general.friends', [
            'user' => $user,
            'friendInvitations' => $user->friendInvitations,
        ]);
    })->name('dashboard.friends');

    Route::post('/dashboard/friends/invite', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

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

        return redirect()
            ->route('dashboard.friends')
            ->with('invite_success', $validated['name'].' has been invited successfully and the email has been sent.');
    })->name('dashboard.friends.invite');

    Route::post('/dashboard/friends/{friendInvitation}/resend', function (Request $request, FriendInvitation $friendInvitation) {
        abort_unless($friendInvitation->user_id === $request->user()->id, 403);

        if ($friendInvitation->verified_at) {
            return redirect()->route('dashboard.friends')->with('invite_success', $friendInvitation->name.' is already verified, so no resend was needed.');
        }

        $verificationUrl = URL::temporarySignedRoute(
            'friend-invitations.verify',
            now()->addDays(7),
            ['friendInvitation' => $friendInvitation->id],
        );

        Mail::to($friendInvitation->email)->send(
            new FriendInvitationMail($friendInvitation, $request->user(), $verificationUrl)
        );

        return redirect()
            ->route('dashboard.friends')
            ->with('invite_success', 'Invitation email resent to '.$friendInvitation->email.'.');
    })->name('dashboard.friends.resend');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


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
        ])->filter(fn (array $method) => $method['enabled'])->values();
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
    })->name('dashboard.buy-shares.subscribe');

    Route::post('/dashboard/buy-shares/{investmentOrder}/proof', function (Request $request, InvestmentOrder $investmentOrder) {
        MiningPlatform::ensureDefaults();

        abort_unless($investmentOrder->user_id === $request->user()->id, 403);
        abort_unless(in_array($investmentOrder->status, ['pending', 'rejected'], true), 403);

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
    })->name('dashboard.buy-shares.proof');

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
























