<?php

use App\Http\Controllers\ProfileController;
use App\Mail\FriendInvitationMail;
use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\PayoutRequest;
use App\Models\Shareholder;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/friend-invitations/{friendInvitation}/verify', function (FriendInvitation $friendInvitation) {
    if (! $friendInvitation->verified_at) {
        $friendInvitation->forceFill(['verified_at' => now()])->save();
    }

    return view('friend-invitations.verified', [
        'friendInvitation' => $friendInvitation,
    ]);
})->middleware(['signed', 'throttle:6,1'])->name('friend-invitations.verify');

Route::get('/dashboard', function () {
    MiningPlatform::ensureDefaults();

    $user = request()->user()->load(['userLevel', 'shareholder', 'investments.package', 'friendInvitations']);
    $level = MiningPlatform::syncUserLevel($user);
    $user->load(['userLevel', 'shareholder', 'investments.package']);

    $miner = Miner::with(['performanceLogs', 'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')])
        ->where('slug', 'alpha-one')
        ->firstOrFail();

    $performanceLogs = $miner->performanceLogs()
        ->orderBy('logged_on')
        ->take(7)
        ->get();

    $sharesSold = MiningPlatform::totalSharesSold($miner);
    $availableShares = max($miner->total_shares - $sharesSold, 0);
    $expectedMonthlyEarnings = MiningPlatform::expectedMonthlyEarnings($user);
    $activeInvestment = $user->investments->firstWhere('status', 'active');
    $totalInvested = (float) $user->investments->where('status', 'active')->sum('amount');
    $registeredReferrals = $user->friendInvitations->whereNotNull('registered_at')->count();
    $verifiedReferrals = $user->friendInvitations->whereNotNull('verified_at')->count();
    $pendingReferrals = $user->friendInvitations->whereNull('verified_at')->count();

    return view('dashboard', [
        'user' => $user,
        'miner' => $miner,
        'level' => $level,
        'sharesSold' => $sharesSold,
        'availableShares' => $availableShares,
        'expectedMonthlyEarnings' => $expectedMonthlyEarnings,
        'activeInvestment' => $activeInvestment,
        'totalInvested' => $totalInvested,
        'registeredReferrals' => $registeredReferrals,
        'verifiedReferrals' => $verifiedReferrals,
        'pendingReferrals' => $pendingReferrals,
        'performanceLabels' => $performanceLogs->map(fn ($log) => $log->logged_on->format('M d'))->values(),
        'performanceRevenueData' => $performanceLogs->map(fn ($log) => round((float) $log->revenue_usd, 2))->values(),
        'performanceHashrateData' => $performanceLogs->map(fn ($log) => round((float) $log->hashrate_th, 2))->values(),
        'performanceUptimeData' => $performanceLogs->map(fn ($log) => round((float) $log->uptime_percentage, 2))->values(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/profile', function () {
        return view('pages.general.profile', [
            'user' => request()->user(),
        ]);
    })->name('dashboard.profile');

    Route::get('/dashboard/wallet', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['userLevel', 'earnings.investment.package', 'investments.package', 'payoutRequests']);
        $wallet = MiningPlatform::walletSummary($user);

        return view('pages.general.wallet', [
            'user' => $user,
            'wallet' => $wallet,
            'earnings' => $user->earnings,
            'payoutRequests' => $user->payoutRequests,
            'activeInvestments' => $user->investments->where('status', 'active')->values(),
            'expectedMonthlyEarnings' => MiningPlatform::expectedMonthlyEarnings($user),
        ]);
    })->name('dashboard.wallet');

    Route::post('/dashboard/wallet/request', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load('earnings');
        $wallet = MiningPlatform::walletSummary($user);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:btc_wallet,usdt_wallet,bank_transfer'],
            'destination' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > $wallet['available']) {
            return back()->withErrors(['amount' => 'Requested amount exceeds available wallet balance.'])->withInput();
        }

        MiningPlatform::createPayoutRequest(
            $user,
            (float) $validated['amount'],
            $validated['method'],
            $validated['destination'],
            $validated['notes'] ?? null,
        );

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
        Route::get('/dashboard/operations', function () {
        MiningPlatform::ensureDefaults();

        return view('pages.general.operations', [
            'payoutRequests' => PayoutRequest::with(['user', 'earnings'])->latest('requested_at')->get(),
        ]);
    })->name('dashboard.operations');

        Route::post('/dashboard/operations/payouts/{payoutRequest}/approve', function (PayoutRequest $payoutRequest) {
        MiningPlatform::approvePayoutRequest($payoutRequest);

        return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request approved successfully.');
    })->name('dashboard.operations.payouts.approve');

        Route::post('/dashboard/operations/payouts/{payoutRequest}/pay', function (PayoutRequest $payoutRequest) {
        MiningPlatform::markPayoutRequestPaid($payoutRequest);

        return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request marked as paid.');
    })->name('dashboard.operations.payouts.pay');

        Route::get('/dashboard/miner', function () {
        MiningPlatform::ensureDefaults();

        $miner = Miner::with([
            'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order'),
            'performanceLogs' => fn ($query) => $query->orderByDesc('logged_on')->limit(10),
        ])->where('slug', 'alpha-one')->firstOrFail();

        $sharesSold = MiningPlatform::totalSharesSold($miner);

        return view('pages.general.miner', [
            'miner' => $miner,
            'sharesSold' => $sharesSold,
            'availableShares' => max($miner->total_shares - $sharesSold, 0),
            'recentLogs' => $miner->performanceLogs,
        ]);
    })->name('dashboard.miner');

    Route::post('/dashboard/miner', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_shares' => ['required', 'integer', 'min:1'],
            'share_price' => ['required', 'numeric', 'min:1'],
            'daily_output_usd' => ['required', 'numeric', 'min:0'],
            'monthly_output_usd' => ['required', 'numeric', 'min:0'],
            'base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'status' => ['required', 'in:active,paused,maintenance'],
        ]);

        $miner->update($validated);

        return redirect()
            ->route('dashboard.miner')
            ->with('miner_success', $miner->name.' details have been updated successfully.');
    })->name('dashboard.miner.update');

    Route::post('/dashboard/miner/logs', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $miner = Miner::where('slug', 'alpha-one')->firstOrFail();

        $validated = $request->validate([
            'logged_on' => ['required', 'date'],
            'revenue_usd' => ['required', 'numeric', 'min:0'],
            'hashrate_th' => ['required', 'numeric', 'min:0'],
            'uptime_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

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
            ->route('dashboard.miner')
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::group(['prefix' => 'email'], function () {
        Route::view('inbox', 'pages.email.inbox');
        Route::view('read', 'pages.email.read');
        Route::view('compose', 'pages.email.compose');
    });

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

    Route::get('/general/sell-products', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['shareholder', 'userLevel', 'investments.package']);
        $level = MiningPlatform::syncUserLevel($user);
        $user->load(['shareholder', 'userLevel', 'investments.package']);

        $miner = Miner::with(['packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')])
            ->where('slug', 'alpha-one')
            ->firstOrFail();

        $sharesSold = MiningPlatform::totalSharesSold($miner);

        return view('pages.general.sell-products', [
            'user' => $user,
            'level' => $level,
            'miner' => $miner,
            'packages' => $miner->packages,
            'shareholder' => $user->shareholder,
            'activeInvestment' => $user->investments->firstWhere('status', 'active'),
            'sharesSold' => $sharesSold,
            'availableShares' => max($miner->total_shares - $sharesSold, 0),
        ]);
    })->name('general.sell-products');

    Route::post('/general/sell-products/subscribe', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $validated = $request->validate([
            'package' => ['required', 'string', 'exists:investment_packages,slug'],
        ]);

        $user = $request->user()->load(['shareholder', 'userLevel', 'investments']);
        $package = InvestmentPackage::with('miner')
            ->where('slug', $validated['package'])
            ->where('is_active', true)
            ->firstOrFail();

        $level = MiningPlatform::syncUserLevel($user);

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
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        $user->forceFill(['account_type' => 'shareholder'])->save();

        $level = MiningPlatform::syncUserLevel($user->fresh());

        UserInvestment::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['level_bonus_rate' => $level->bonus_rate]);

        Earning::firstOrCreate(
            [
                'user_id' => $user->id,
                'investment_id' => $investment->id,
                'earned_on' => now()->toDateString(),
                'source' => 'projected_return',
            ],
            [
                'amount' => round((float) $package->price * ((float) $package->monthly_return_rate + (float) $level->bonus_rate), 2),
                'status' => 'pending',
                'notes' => 'Initial projected monthly return generated after package subscription.',
            ],
        );

        MiningPlatform::awardReferralSubscription($user, $investment);

        return redirect()
            ->route('general.sell-products')
            ->with('subscription_success', 'You are now subscribed to the '.$package->name.' package and your mining shares are active.');
    })->name('general.sell-products.subscribe');

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
