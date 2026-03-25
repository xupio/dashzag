<?php

use App\Models\FriendInvitation;
use App\Models\User;
use Database\Seeders\DemoInvestorNetworkSeeder;

test('demo investor network seeder creates realistic network data', function () {
    $this->seed(DemoInvestorNetworkSeeder::class);

    expect(User::query()->where('email', 'like', '%@zagchain.test')->count())->toBe(20);
    expect(User::query()->where('email', 'like', 'demo-investor-%')->whereNotNull('email_verified_at')->count())->toBeGreaterThan(10);
    expect(User::query()->where('email', 'like', 'demo-investor-%')->whereNull('email_verified_at')->count())->toBeGreaterThan(0);
    expect(User::query()->where('email', 'like', 'demo-investor-%')->whereNotNull('sponsor_user_id')->count())->toBeGreaterThan(10);
    expect(FriendInvitation::query()->where('email', 'like', '%@zagchain.test')->whereNotNull('verified_at')->count())->toBeGreaterThan(10);
    expect(FriendInvitation::query()->where('email', 'like', '%@zagchain.test')->whereNull('verified_at')->count())->toBeGreaterThan(0);
    expect(FriendInvitation::query()->where('email', 'like', '%@zagchain.test')->whereNotNull('registered_at')->count())->toBeGreaterThan(5);
});
