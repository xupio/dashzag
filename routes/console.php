<?php

use App\Models\User;
use App\Models\UserInvestment;
use App\Notifications\AdminHealthSummaryNotification;
use App\Services\MinerLifecycleService;
use App\Support\MiningPlatform;
use App\Notifications\DigestSummaryNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-digests {--frequency= : Limit sending to daily or weekly users}', function () {
    $frequencyOption = $this->option('frequency');

    if ($frequencyOption && ! in_array($frequencyOption, ['daily', 'weekly'], true)) {
        $this->error('The frequency option must be daily or weekly.');

        return self::FAILURE;
    }

    MiningPlatform::ensureDefaults();

    $sent = 0;
    $evaluated = 0;

    User::query()
        ->whereNotNull('email_verified_at')
        ->orderBy('id')
        ->get()
        ->each(function (User $user) use ($frequencyOption, &$sent, &$evaluated) {
            $frequency = $user->digestFrequency();

            if ($frequencyOption && $frequency !== $frequencyOption) {
                return;
            }

            $evaluated++;

            if (empty($user->notificationChannelsFor('digest'))) {
                return;
            }

            $alreadySent = $frequency === 'daily'
                ? optional($user->last_daily_digest_sent_at)?->isToday()
                : optional($user->last_weekly_digest_sent_at)?->greaterThanOrEqualTo(now()->startOfWeek());

            if ($alreadySent) {
                return;
            }

            $summary = MiningPlatform::digestSummaryForUser($user, $frequency);

            if (($summary['total'] ?? 0) <= 0) {
                return;
            }

            $user->notify(new DigestSummaryNotification($summary['frequency'], $summary, $summary['period_label'], 'scheduled'));

            $user->forceFill([
                $frequency === 'daily' ? 'last_daily_digest_sent_at' : 'last_weekly_digest_sent_at' => now(),
            ])->save();

            $sent++;
        });

    $this->info('Evaluated '.$evaluated.' users and sent '.$sent.' digest notifications.');

    return self::SUCCESS;
})->purpose('Send scheduled digest notifications to users based on their saved frequency.');

Artisan::command('miners:generate-daily-snapshots {--date= : Generate snapshots for a specific date (Y-m-d)} {--miner= : Limit generation to a specific miner slug}', function () {
    MiningPlatform::ensureDefaults();

    $dateOption = $this->option('date') ?: now()->toDateString();
    $minerOption = $this->option('miner');

    if ($minerOption) {
        $miners = collect([MiningPlatform::resolveMiner($minerOption)]);
    } else {
        $miners = MiningPlatform::activeMiners();
    }

    $generated = 0;
    $earningsSynced = 0;
    $netProfitTotal = 0;

    $miners->each(function ($miner) use (&$generated, &$earningsSynced, &$netProfitTotal, $dateOption) {
        $log = MiningPlatform::generateAutomaticPerformanceLog($miner, $dateOption);
        $generated++;
        $earningsSynced += $miner->investments()->where('status', 'active')->where('amount', '>', 0)->count();
        $netProfitTotal += (float) $log->net_profit_usd;
    });

    $this->info('Generated '.$generated.' miner snapshots for '.$dateOption.'.');
    $this->info('Synced '.$earningsSynced.' active investment earnings.');
    $this->info('Net profit total: $'.number_format($netProfitTotal, 2));

    return self::SUCCESS;
})->purpose('Generate daily miner performance snapshots and sync per-share earnings.');

Artisan::command('miners:sync-lifecycle', function (MinerLifecycleService $lifecycleService) {
    $updatedMiners = $lifecycleService->syncEligibleMiners();
    $changedMiners = $updatedMiners->filter(function ($miner) {
        return in_array($miner->status, ['mature', 'secondary_market_open'], true);
    });

    $this->info('Evaluated '.$updatedMiners->count().' lifecycle-eligible miners.');
    $this->info('Miners now mature or secondary market open: '.$changedMiners->count().'.');

    return self::SUCCESS;
})->purpose('Advance sold-out miners into maturity and secondary-market availability.');

Artisan::command('admin:send-health-summary', function () {
    MiningPlatform::ensureDefaults();

    $summary = MiningPlatform::adminHealthSummary();
    $sent = 0;

    User::query()
        ->where('role', 'admin')
        ->whereNotNull('email_verified_at')
        ->orderBy('id')
        ->get()
        ->each(function (User $admin) use ($summary, &$sent) {
            $alreadySent = $admin->notifications()
                ->where('type', AdminHealthSummaryNotification::class)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if ($alreadySent) {
                return;
            }

            $admin->notify(new AdminHealthSummaryNotification($summary));
            $sent++;
        });

    $this->info('Sent '.$sent.' admin health summary notifications.');

    return self::SUCCESS;
})->purpose('Send the daily admin health summary notification to verified admins.');

Artisan::command('hall-of-fame:capture {--category= : Limit capture to weekly or monthly snapshots}', function () {
    MiningPlatform::ensureDefaults();

    $categoryOption = $this->option('category');

    if ($categoryOption && ! in_array($categoryOption, ['weekly', 'monthly'], true)) {
        $this->error('The category option must be weekly or monthly.');

        return self::FAILURE;
    }

    $categories = $categoryOption ? [$categoryOption] : ['weekly', 'monthly'];

    foreach ($categories as $category) {
        $leaders = MiningPlatform::captureCompetitionSnapshot($category);
        $winner = $leaders->first();

        $this->info(ucfirst($category).' snapshot captured for '.$leaders->count().' ranked users.');

        if ($winner) {
            $metric = $category === 'weekly' ? $winner['weekly_momentum'] : $winner['monthly_momentum'];
            $this->line('Winner: '.$winner['user']->name.' with '.$metric['score'].' points.');
        }
    }

    return self::SUCCESS;
})->purpose('Capture weekly and monthly Hall of Fame snapshots.');

Artisan::command('miners:repair-daily-share-earnings {--user= : Limit repair to one user ID} {--investment= : Limit repair to one investment ID} {--dry-run : Preview affected investments without changing data}', function () {
    $userOption = $this->option('user');
    $investmentOption = $this->option('investment');
    $dryRun = (bool) $this->option('dry-run');

    MiningPlatform::ensureDefaults();

    $investments = UserInvestment::query()
        ->with(['user', 'miner', 'package'])
        ->where('amount', '>', 0)
        ->when($userOption, fn ($query) => $query->where('user_id', (int) $userOption))
        ->when($investmentOption, fn ($query) => $query->where('id', (int) $investmentOption))
        ->orderBy('id')
        ->get();

    if ($investments->isEmpty()) {
        $this->warn('No matching investments found.');

        return self::SUCCESS;
    }

    $affected = 0;
    $deleted = 0;
    $rebuilt = 0;

    $investments->each(function (UserInvestment $investment) use ($dryRun, &$affected, &$deleted, &$rebuilt) {
        if (! $investment->subscribed_at) {
            return;
        }

        $invalidCount = $investment->earnings()
            ->where('source', 'mining_daily_share')
            ->whereDate('earned_on', '<', $investment->subscribed_at->toDateString())
            ->count();

        if ($invalidCount === 0) {
            return;
        }

        $affected++;

        $label = 'Investment #'.$investment->id
            .' user='.($investment->user?->email ?? $investment->user_id)
            .' invalid_rows='.$invalidCount
            .' subscribed_at='.$investment->subscribed_at->toDateString();

        if ($dryRun) {
            $this->line('[dry-run] '.$label);

            return;
        }

        $result = MiningPlatform::rebuildMiningDailyShareEarningsForInvestment($investment);

        $deleted += (int) ($result['deleted'] ?? 0);
        $rebuilt += (int) ($result['rebuilt'] ?? 0);

        $this->info($label.' deleted='.$result['deleted'].' rebuilt='.$result['rebuilt']);
    });

    if ($dryRun) {
        $this->info('Dry run complete. Affected investments: '.$affected.'.');

        return self::SUCCESS;
    }

    $this->info('Repair complete. Affected investments: '.$affected.'.');
    $this->info('Deleted mining_daily_share rows: '.$deleted.'.');
    $this->info('Rebuilt mining_daily_share rows: '.$rebuilt.'.');

    return self::SUCCESS;
})->purpose('Repair backdated mining daily share earnings by rebuilding affected investments from their subscription date.');

Schedule::command('notifications:send-digests --frequency=daily')->dailyAt('09:00');
Schedule::command('notifications:send-digests --frequency=weekly')->weeklyOn(1, '09:30');
Schedule::command('miners:generate-daily-snapshots')->dailyAt('00:15');
Schedule::command('miners:sync-lifecycle')->dailyAt('00:25');
Schedule::command('hall-of-fame:capture --category=weekly')->weeklyOn(1, '00:35');
Schedule::command('hall-of-fame:capture --category=monthly')->monthlyOn(1, '00:45');
Schedule::command('admin:send-health-summary')->dailyAt('10:00');
