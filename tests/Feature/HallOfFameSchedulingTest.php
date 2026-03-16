<?php

use App\Models\HallOfFameSnapshot;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('hall of fame capture command stores weekly and monthly winners without duplicates', function () {
    $user = User::factory()->create([
        'name' => 'Leaderboard User',
        'email_verified_at' => now(),
    ]);

    Artisan::call('hall-of-fame:capture');
    Artisan::call('hall-of-fame:capture');

    expect(HallOfFameSnapshot::query()->where('category', 'weekly')->exists())->toBeTrue();
    expect(HallOfFameSnapshot::query()->where('category', 'monthly')->exists())->toBeTrue();
    expect(HallOfFameSnapshot::query()->where('category', 'weekly')->where('rank_position', 1)->count())->toBe(1);
    expect(HallOfFameSnapshot::query()->where('category', 'monthly')->where('rank_position', 1)->count())->toBe(1);
    expect($user->fresh()->notifications->where('type', ActivityFeedNotification::class)->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'hall_of_fame_weekly_winner')->count())->toBe(1);
    expect($user->fresh()->notifications->where('type', ActivityFeedNotification::class)->filter(fn ($notification) => ($notification->data['event_key'] ?? null) === 'hall_of_fame_monthly_winner')->count())->toBe(1);
});

test('hall of fame capture command can target one category', function () {
    User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Artisan::call('hall-of-fame:capture', ['--category' => 'weekly']);

    expect(HallOfFameSnapshot::query()->where('category', 'weekly')->exists())->toBeTrue();
    expect(HallOfFameSnapshot::query()->where('category', 'monthly')->exists())->toBeFalse();
});
