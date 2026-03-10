<?php

use App\Mail\FriendInvitationMail;
use App\Models\FriendInvitation;
use App\Models\User;
use App\Http\Controllers\ProfileController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;

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
    $startOfThisMonth = Carbon::now()->startOfMonth();
    $startOfLastMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth();
    $endOfLastMonth = Carbon::now()->subMonthNoOverflow()->endOfMonth();

    $newCustomersCount = User::where('created_at', '>=', $startOfThisMonth)->count();
    $lastMonthCustomersCount = User::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

    $newCustomersGrowth = $lastMonthCustomersCount > 0
        ? (($newCustomersCount - $lastMonthCustomersCount) / $lastMonthCustomersCount) * 100
        : ($newCustomersCount > 0 ? 100 : 0);

    $customersTrendMonths = collect(range(5, 0))->map(function ($offset) {
        return Carbon::now()->subMonthsNoOverflow($offset);
    });

    $customersChartLabels = $customersTrendMonths->map(function ($month) {
        return $month->format('M');
    })->values();

    $customersChartData = $customersTrendMonths->map(function ($month) {
        return User::whereBetween('created_at', [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
        ])->count();
    })->values();

    $btcChartLabels = collect();
    $btcChartData = collect();
    $btcLatestPrice = null;
    $btcMonthlyChange = 0.0;

    $btcResponse = Http::timeout(10)->get('https://api.coingecko.com/api/v3/coins/bitcoin/market_chart', [
        'vs_currency' => 'usd',
        'days' => 90,
        'interval' => 'daily',
    ]);

    if ($btcResponse->successful()) {
        $prices = collect(data_get($btcResponse->json(), 'prices', []));

        $btcLastMonth = Carbon::now()->subMonthNoOverflow();
        $btcStart = $btcLastMonth->copy()->startOfMonth();
        $btcEnd = $btcLastMonth->copy()->endOfMonth();

        $btcLastMonthPrices = $prices->map(function ($point) {
            return [
                'date' => Carbon::createFromTimestampMs((int) $point[0]),
                'price' => (float) $point[1],
            ];
        })->filter(function ($point) use ($btcStart, $btcEnd) {
            return $point['date']->betweenIncluded($btcStart, $btcEnd);
        })->values();

        $btcChartLabels = $btcLastMonthPrices->map(function ($point) {
            return $point['date']->format('M d');
        })->values();

        $btcChartData = $btcLastMonthPrices->map(function ($point) {
            return round($point['price'], 2);
        })->values();

        if ($btcLastMonthPrices->isNotEmpty()) {
            $btcLatestPrice = $btcLastMonthPrices->last()['price'];
            $btcFirstPrice = $btcLastMonthPrices->first()['price'];

            $btcMonthlyChange = $btcFirstPrice > 0
                ? (($btcLatestPrice - $btcFirstPrice) / $btcFirstPrice) * 100
                : 0.0;
        }
    }

    $difficultyChartLabels = collect();
    $difficultyChartData = collect();
    $difficultyLatestT = null;
    $difficultyMonthlyChange = 0.0;

    $difficultyResponse = Http::timeout(10)->get('https://api.blockchain.info/charts/difficulty', [
        'timespan' => '90days',
        'format' => 'json',
        'sampled' => 'true',
    ]);

    if ($difficultyResponse->successful()) {
        $difficultyValues = collect(data_get($difficultyResponse->json(), 'values', []));

        $diffLastMonth = Carbon::now()->subMonthNoOverflow();
        $diffStart = $diffLastMonth->copy()->startOfMonth();
        $diffEnd = $diffLastMonth->copy()->endOfMonth();

        $difficultyLastMonth = $difficultyValues->map(function ($point) {
            return [
                'date' => Carbon::createFromTimestamp((int) data_get($point, 'x')),
                'difficulty' => (float) data_get($point, 'y'),
            ];
        })->filter(function ($point) use ($diffStart, $diffEnd) {
            return $point['date']->betweenIncluded($diffStart, $diffEnd);
        })->values();

        $difficultyChartLabels = $difficultyLastMonth->map(function ($point) {
            return $point['date']->format('M d');
        })->values();

        $difficultyChartData = $difficultyLastMonth->map(function ($point) {
            return round($point['difficulty'] / 1_000_000_000_000, 2);
        })->values();

        if ($difficultyLastMonth->isNotEmpty()) {
            $difficultyLatestRaw = $difficultyLastMonth->last()['difficulty'];
            $difficultyFirstRaw = $difficultyLastMonth->first()['difficulty'];

            $difficultyLatestT = $difficultyLatestRaw / 1_000_000_000_000;
            $difficultyMonthlyChange = $difficultyFirstRaw > 0
                ? (($difficultyLatestRaw - $difficultyFirstRaw) / $difficultyFirstRaw) * 100
                : 0.0;
        }
    }

    return view('dashboard', [
        'newCustomersCount' => $newCustomersCount,
        'newCustomersGrowth' => $newCustomersGrowth,
        'customersChartLabels' => $customersChartLabels,
        'customersChartData' => $customersChartData,
        'btcChartLabels' => $btcChartLabels,
        'btcChartData' => $btcChartData,
        'btcLatestPrice' => $btcLatestPrice,
        'btcMonthlyChange' => $btcMonthlyChange,
        'difficultyChartLabels' => $difficultyChartLabels,
        'difficultyChartData' => $difficultyChartData,
        'difficultyLatestT' => $difficultyLatestT,
        'difficultyMonthlyChange' => $difficultyMonthlyChange,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/profile', function () {
        return view('pages.general.profile', [
            'user' => request()->user(),
        ]);
    })->name('dashboard.profile');

    Route::get('/dashboard/friends', function () {
        $user = request()->user();

        return view('pages.general.friends', [
            'user' => $user,
            'friendInvitations' => $user->friendInvitations,
        ]);
    })->name('dashboard.friends');

    Route::post('/dashboard/friends/invite', function (\Illuminate\Http\Request $request) {
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










