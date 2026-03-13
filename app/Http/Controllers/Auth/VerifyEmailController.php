<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FriendInvitation;
use App\Notifications\ActivityFeedNotification;
use App\Support\MiningPlatform;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            $user = $request->user();
            $sponsor = MiningPlatform::assignSponsorFromInvitations($user);
            $invitations = FriendInvitation::with('user')->where('email', $user->email)->get();

            FriendInvitation::where('email', $user->email)
                ->update(['registered_at' => now()]);

            MiningPlatform::awardReferralRegistration($user);

            foreach ($invitations as $invitation) {
                $networkJoinTemplate = MiningPlatform::activityTemplate('network_join', [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ]);

                $invitation->user?->notify(new ActivityFeedNotification([
                    'category' => 'referral',
                    'status' => 'info',
                    'subject' => $networkJoinTemplate['subject'],
                    'message' => $networkJoinTemplate['message'],
                    'context_label' => 'Team member',
                    'context_value' => $user->email,
                    'related_user_id' => $user->id,
                ]));

                $registrationRewardTemplate = MiningPlatform::activityTemplate('reward_registration', [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ]);

                $invitation->user?->notify(new ActivityFeedNotification([
                    'category' => 'reward',
                    'status' => 'success',
                    'subject' => $registrationRewardTemplate['subject'],
                    'message' => $registrationRewardTemplate['message'],
                    'context_label' => 'Referred user',
                    'context_value' => $user->email,
                    'related_user_id' => $user->id,
                    'amount' => (float) MiningPlatform::rewardSetting('referral_registration_reward'),
                    'amount_label' => 'Reward amount',
                ]));
            }

            if ($sponsor) {
                $sponsorTemplate = MiningPlatform::activityTemplate('network_sponsor', [
                    'sponsor_name' => $sponsor->name,
                    'sponsor_email' => $sponsor->email,
                ]);

                $user->notify(new ActivityFeedNotification([
                    'category' => 'network',
                    'status' => 'info',
                    'subject' => $sponsorTemplate['subject'],
                    'message' => $sponsorTemplate['message'],
                    'context_label' => 'Sponsor',
                    'context_value' => $sponsor->email,
                    'related_user_id' => $sponsor->id,
                ]));

                $freshSponsor = $sponsor->fresh();
                MiningPlatform::refreshInvestmentBonusRates($freshSponsor);
                $upgradeInvestment = MiningPlatform::attemptStarterUpgrade($freshSponsor);

                if ($upgradeInvestment) {
                    $upgradeTemplate = MiningPlatform::activityTemplate('basic_unlocked', [
                        'package_name' => $upgradeInvestment->package?->name ?? 'Basic 100',
                    ]);

                    $freshSponsor->notify(new ActivityFeedNotification([
                        'category' => 'milestone',
                        'status' => 'success',
                        'subject' => $upgradeTemplate['subject'],
                        'message' => $upgradeTemplate['message'],
                        'context_label' => 'Unlocked package',
                        'context_value' => $upgradeInvestment->package?->name ?? 'Basic 100',
                        'investment_id' => $upgradeInvestment->id,
                        'amount' => (float) $upgradeInvestment->amount,
                        'amount_label' => 'Package value',
                    ]));
                }
            }

            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}