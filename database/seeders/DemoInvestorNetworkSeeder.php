<?php

namespace Database\Seeders;

use App\Models\FriendInvitation;
use App\Models\InvestmentPackage;
use App\Models\ReferralEvent;
use App\Models\Shareholder;
use App\Models\User;
use App\Models\UserInvestment;
use App\Support\MiningPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoInvestorNetworkSeeder extends Seeder
{
    /**
     * Seed a realistic-looking investor network for demos.
     */
    public function run(): void
    {
        MiningPlatform::ensureDefaults();

        Model::withoutEvents(function () {
            DB::transaction(function () {
                $this->seedDemoNetwork();
            });
        });
    }

    protected function seedDemoNetwork(): void
    {
        $rootMinerPackages = InvestmentPackage::query()
            ->whereHas('miner', fn ($query) => $query->where('slug', 'alpha-one'))
            ->get()
            ->keyBy('slug');

        $packagePlan = [
            1 => 'scale-1000',
            2 => 'growth-500',
            3 => 'growth-500',
            4 => 'starter-100',
            5 => 'starter-100',
            6 => 'growth-500',
            7 => 'starter-100',
            8 => 'scale-1000',
            9 => null,
            10 => 'starter-100',
            11 => null,
            12 => 'growth-500',
            13 => 'starter-100',
            14 => null,
            15 => 'starter-100',
            16 => null,
            17 => 'growth-500',
            18 => null,
            19 => 'starter-100',
            20 => null,
        ];

        $sponsorMap = [
            1 => null,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 2,
            7 => 2,
            8 => 3,
            9 => 3,
            10 => 4,
            11 => 4,
            12 => 5,
            13 => 6,
            14 => 6,
            15 => 8,
            16 => 8,
            17 => 10,
            18 => 12,
            19 => 15,
            20 => 17,
        ];

        $names = [
            'Ava Morgan', 'Noah Carter', 'Liam Bennett', 'Mia Hayes', 'Ethan Brooks',
            'Sofia Reed', 'Lucas Ward', 'Emma Cooper', 'Mason Price', 'Isabella Bell',
            'James Foster', 'Charlotte Ross', 'Benjamin Gray', 'Amelia Long', 'Elijah Kelly',
            'Harper Cox', 'Henry Hughes', 'Evelyn Diaz', 'Alexander Perry', 'Ella Murphy',
        ];

        $users = collect();
        $baseDate = Carbon::now()->subDays(75);

        foreach ($names as $index => $name) {
            $number = $index + 1;
            $createdAt = $baseDate->copy()->addDays($number * 2);
            $verified = $number <= 16 || in_array($number, [18, 20], true);
            $sponsorUser = ($sponsorMap[$number] ?? null) ? $users[$sponsorMap[$number]] : null;

            $user = User::query()->updateOrCreate(
                ['email' => sprintf('demo-investor-%02d@zagchain.test', $number)],
                [
                    'name' => $name,
                    'password' => bcrypt('password'),
                    'role' => 'user',
                    'account_type' => $packagePlan[$number] ? 'shareholder' : 'starter',
                    'email_verified_at' => $verified ? $createdAt->copy()->addHours(4) : null,
                    'sponsor_user_id' => $sponsorUser?->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );

            $users->put($number, $user);
        }

        $users->each(function (User $user, int $number) use ($users, $sponsorMap) {
            $this->seedReferralInvitationForRegisteredUser($user, $users, $sponsorMap, $number);
            $this->seedExtraInvitations($user, $number);
        });

        $users->each(function (User $user, int $number) use ($packagePlan, $rootMinerPackages) {
            $packageSlug = $packagePlan[$number] ?? null;

            if (! $packageSlug) {
                MiningPlatform::syncUserLevel($user);

                return;
            }

            $package = $rootMinerPackages->get($packageSlug);

            if (! $package) {
                return;
            }

            $shareholder = Shareholder::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'package_name' => $package->name,
                    'price' => $package->price,
                    'billing_cycle' => 'monthly',
                    'units_limit' => $package->units_limit,
                    'status' => 'active',
                    'subscribed_at' => $user->created_at->copy()->addDays(1),
                ],
            );

            UserInvestment::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'miner_id' => $package->miner_id,
                    'shareholder_id' => $shareholder->id,
                    'amount' => $package->price,
                    'shares_owned' => $package->shares_count,
                    'monthly_return_rate' => $package->monthly_return_rate,
                    'level_bonus_rate' => 0,
                    'team_bonus_rate' => 0,
                    'status' => 'active',
                    'subscribed_at' => $user->created_at->copy()->addDays(1),
                ],
            );

            MiningPlatform::syncUserLevel($user);
        });

        $users->each(function (User $user) {
            $registeredReferrals = $user->friendInvitations()
                ->whereNotNull('registered_at')
                ->get();

            foreach ($registeredReferrals as $invitation) {
                $relatedUser = User::query()->where('email', $invitation->email)->first();

                ReferralEvent::query()->firstOrCreate(
                    [
                        'sponsor_user_id' => $user->id,
                        'actor_user_id' => $relatedUser?->id,
                        'related_user_id' => $relatedUser?->id,
                        'type' => 'registration',
                        'title' => 'Demo referral registration',
                    ],
                    [
                        'message' => $invitation->name.' joined the demo network.',
                        'created_at' => $invitation->registered_at ?? now(),
                        'updated_at' => $invitation->registered_at ?? now(),
                    ],
                );
            }
        });
    }

    protected function seedReferralInvitationForRegisteredUser(User $user, Collection $users, array $sponsorMap, int $number): void
    {
        $sponsorNumber = $sponsorMap[$number] ?? null;

        if (! $sponsorNumber) {
            return;
        }

        /** @var User|null $sponsor */
        $sponsor = $users->get($sponsorNumber);

        if (! $sponsor) {
            return;
        }

        FriendInvitation::query()->updateOrCreate(
            [
                'user_id' => $sponsor->id,
                'email' => $user->email,
            ],
            [
                'name' => $user->name,
                'phone' => '+97150000'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'country' => 'AE',
                'verified_at' => $user->email_verified_at,
                'registered_at' => $user->created_at,
                'created_at' => $user->created_at->copy()->subHours(12),
                'updated_at' => $user->created_at,
            ],
        );
    }

    protected function seedExtraInvitations(User $user, int $number): void
    {
        $extraInvitationCount = match (true) {
            $number <= 3 => 4,
            $number <= 8 => 3,
            $number <= 14 => 2,
            default => 1,
        };

        for ($offset = 1; $offset <= $extraInvitationCount; $offset++) {
            $createdAt = $user->created_at->copy()->addDays($offset);
            $isVerified = $offset % 2 === 1;
            $isRegistered = $offset === 1 && $number % 3 !== 0;

            $name = 'Demo Invite '.Str::upper(Str::random(4));
            $email = sprintf('invite-%02d-%02d@zagchain.test', $number, $offset);

            FriendInvitation::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'email' => $email,
                ],
                [
                    'name' => $name,
                    'phone' => '+97151111'.str_pad((string) ($number * 10 + $offset), 4, '0', STR_PAD_LEFT),
                    'country' => $number % 2 === 0 ? 'AE' : 'SA',
                    'verified_at' => $isVerified ? $createdAt->copy()->addHours(8) : null,
                    'registered_at' => $isRegistered ? $createdAt->copy()->addDays(2) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );
        }
    }
}
