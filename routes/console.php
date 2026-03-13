<?php

use App\Models\User;
use App\Notifications\DigestSummaryNotification;
use App\Support\MiningPlatform;
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

Schedule::command('notifications:send-digests --frequency=daily')->dailyAt('09:00');
Schedule::command('notifications:send-digests --frequency=weekly')->weeklyOn(1, '09:30');
