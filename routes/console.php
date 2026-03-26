<?php

use App\Models\User;
use App\Notifications\AdminHealthSummaryNotification;
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

Schedule::command('notifications:send-digests --frequency=daily')->dailyAt('09:00');
Schedule::command('notifications:send-digests --frequency=weekly')->weeklyOn(1, '09:30');
Schedule::command('miners:generate-daily-snapshots')->dailyAt('00:15');
Schedule::command('hall-of-fame:capture --category=weekly')->weeklyOn(1, '00:35');
Schedule::command('hall-of-fame:capture --category=monthly')->monthlyOn(1, '00:45');
Schedule::command('admin:send-health-summary')->dailyAt('10:00');
