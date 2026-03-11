<?php

use App\Http\Controllers\ProfileController;
use App\Mail\FriendInvitationMail;
use App\Models\Earning;
use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\Miner;
use App\Models\MinerPerformanceLog;
use App\Models\PayoutRequest;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
use App\Models\User;
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

Route::get('/dashboard', function (Request $request) {
    MiningPlatform::ensureDefaults();

    $user = $request->user()->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner', 'friendInvitations']);
    $level = MiningPlatform::syncUserLevel($user);
    $user->load(['userLevel', 'shareholder', 'investments.package', 'investments.miner']);

    $miners = MiningPlatform::activeMiners();
    $miner = MiningPlatform::resolveMiner($request->query('miner'));
    $miner->load(['performanceLogs', 'packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')]);

    $performanceLogs = $miner->performanceLogs()->orderBy('logged_on')->take(7)->get();
    $sharesSold = MiningPlatform::totalSharesSold($miner);

    return view('dashboard', [
        'user' => $user,
        'miners' => $miners,
        'miner' => $miner,
        'level' => $level,
        'sharesSold' => $sharesSold,
        'availableShares' => max($miner->total_shares - $sharesSold, 0),
        'expectedMonthlyEarnings' => MiningPlatform::expectedMonthlyEarnings($user),
        'activeInvestment' => $user->investments->where('status', 'active')->firstWhere('miner_id', $miner->id),
        'totalInvested' => (float) $user->investments->where('status', 'active')->sum('amount'),
        'registeredReferrals' => $user->friendInvitations->whereNotNull('registered_at')->count(),
        'verifiedReferrals' => $user->friendInvitations->whereNotNull('verified_at')->count(),
        'pendingReferrals' => $user->friendInvitations->whereNull('verified_at')->count(),
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

    Route::get('/dashboard/investments', function () {
        MiningPlatform::ensureDefaults();

        $user = request()->user()->load(['investments.miner', 'investments.package', 'earnings']);
        $activeInvestments = $user->investments->where('status', 'active')->values();

        return view('pages.general.investments', [
            'user' => $user,
            'investments' => $user->investments,
            'activeInvestments' => $activeInvestments,
            'totalInvested' => (float) $activeInvestments->sum('amount'),
            'totalSharesOwned' => (int) $activeInvestments->sum('shares_owned'),
            'expectedMonthlyEarnings' => MiningPlatform::expectedMonthlyEarnings($user),
            'availableEarnings' => (float) $user->earnings->where('status', 'available')->sum('amount'),
        ]);
    })->name('dashboard.investments');

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
        $directTeam = $user->sponsoredUsers->values();
        $secondLevelTeam = $directTeam
            ->flatMap(fn ($member) => $member->sponsoredUsers)
            ->unique('id')
            ->values();

        $referralRewards = $user->earnings
            ->whereIn('source', ['referral_registration', 'referral_subscription', 'team_subscription_bonus', 'team_downline_bonus'])
            ->sortByDesc(fn ($reward) => optional($reward->earned_on)->timestamp ?? 0)
            ->values();

        $activeTeamInvestors = $directTeam->filter(fn ($member) => $member->investments->where('status', 'active')->isNotEmpty())->count();
        $teamCapital = (float) $directTeam->sum(fn ($member) => $member->investments->where('status', 'active')->sum('amount'));

        return view('pages.general.network', [
            'user' => $user,
            'friendInvitations' => $friendInvitations,
            'directTeam' => $directTeam,
            'secondLevelTeam' => $secondLevelTeam,
            'teamEvents' => $user->referralEvents->sortByDesc('created_at')->values(),
            'teamBonusRate' => MiningPlatform::teamBonusRate($user),
            'activeTeamInvestors' => $activeTeamInvestors,
            'teamCapital' => $teamCapital,
            'invitedCount' => $friendInvitations->count(),
            'verifiedCount' => $friendInvitations->whereNotNull('verified_at')->count(),
            'registeredCount' => $friendInvitations->whereNotNull('registered_at')->count(),
            'subscribedCount' => $activeTeamInvestors,
            'referralRewards' => $referralRewards,
            'referralRewardsTotal' => (float) $referralRewards->sum('amount'),
        ]);
    })->name('dashboard.network');

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
        Route::get('/dashboard/miners', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.miners', [
                'miners' => Miner::with(['packages', 'investments'])->orderBy('name')->get(),
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
                'base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
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

            $topInvestors = $users->sortByDesc(fn ($user) => $user->investments->where('status', 'active')->sum('amount'))->take(5)->values();
            $topReferrers = $users->sortByDesc(fn ($user) => $user->friendInvitations->whereNotNull('registered_at')->count())->take(5)->values();

            return view('pages.general.analytics', [
                'totalInvested' => (float) UserInvestment::where('status', 'active')->sum('amount'),
                'totalSharesSold' => (int) UserInvestment::where('status', 'active')->sum('shares_owned'),
                'totalAvailableLiability' => (float) Earning::where('status', 'available')->sum('amount'),
                'totalPendingPayouts' => (float) PayoutRequest::whereIn('status', ['pending', 'approved'])->sum('amount'),
                'totalPaidOut' => (float) Earning::where('status', 'paid')->sum('amount'),
                'activeShareholders' => (int) User::where('account_type', 'shareholder')->count(),
                'packages' => $packages,
                'topInvestors' => $topInvestors,
                'topReferrers' => $topReferrers,
                'miner' => $miner,
                'miners' => $miners,
            ]);
        })->name('dashboard.analytics');

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

        Route::get('/dashboard/operations', function () {
            MiningPlatform::ensureDefaults();

            return view('pages.general.operations', [
                'payoutRequests' => PayoutRequest::with(['user', 'earnings'])->latest('requested_at')->get(),
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

        Route::post('/dashboard/operations/payouts/{payoutRequest}/approve', function (Request $request, PayoutRequest $payoutRequest) {
            $validated = $request->validate([
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            MiningPlatform::approvePayoutRequest($payoutRequest, $validated['admin_notes'] ?? null);

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request approved successfully.');
        })->name('dashboard.operations.payouts.approve');

        Route::post('/dashboard/operations/payouts/{payoutRequest}/pay', function (Request $request, PayoutRequest $payoutRequest) {
            $validated = $request->validate([
                'transaction_reference' => ['nullable', 'string', 'max:255'],
                'admin_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            MiningPlatform::markPayoutRequestPaid(
                $payoutRequest,
                $validated['transaction_reference'] ?? null,
                $validated['admin_notes'] ?? null,
            );

            return redirect()->route('dashboard.operations')->with('operations_success', 'Payout request marked as paid.');
        })->name('dashboard.operations.payouts.pay');

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
                'base_monthly_return_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'status' => ['required', 'in:active,paused,maintenance'],
            ]);

            $miner = Miner::where('slug', $validated['miner_slug'])->firstOrFail();
            unset($validated['miner_slug']);
            $miner->update($validated);

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

    Route::get('/general/sell-products', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $user = $request->user()->load(['shareholder', 'userLevel', 'investments.package', 'investments.miner']);
        $level = MiningPlatform::syncUserLevel($user);
        $user->load(['shareholder', 'userLevel', 'investments.package', 'investments.miner']);

        $miners = MiningPlatform::activeMiners();
        $miner = MiningPlatform::resolveMiner($request->query('miner'));
        $miner->load(['packages' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')]);

        $sharesSold = MiningPlatform::totalSharesSold($miner);

        return view('pages.general.sell-products', [
            'user' => $user,
            'level' => $level,
            'miners' => $miners,
            'miner' => $miner,
            'packages' => $miner->packages,
            'shareholder' => $user->shareholder,
            'activeInvestment' => $user->investments->where('status', 'active')->firstWhere('miner_id', $miner->id),
            'sharesSold' => $sharesSold,
            'availableShares' => max($miner->total_shares - $sharesSold, 0),
        ]);
    })->name('general.sell-products');

    Route::post('/general/sell-products/subscribe', function (Request $request) {
        MiningPlatform::ensureDefaults();

        $validated = $request->validate([
            'package' => ['required', 'string', 'exists:investment_packages,slug'],
        ]);

        $user = $request->user()->load(['shareholder', 'userLevel', 'investments', 'sponsor']);
        $package = InvestmentPackage::with('miner')
            ->where('slug', $validated['package'])
            ->where('is_active', true)
            ->firstOrFail();

        $level = MiningPlatform::syncUserLevel($user);
        $teamBonusRate = MiningPlatform::teamBonusRate($user);

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
        $refreshedUser = MiningPlatform::refreshInvestmentBonusRates($user->fresh());
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
                'notes' => 'Initial projected monthly return generated after package subscription.',
            ],
        );

        MiningPlatform::awardReferralSubscription($refreshedUser, $investment);

        return redirect()
            ->route('general.sell-products', ['miner' => $package->miner?->slug])
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















