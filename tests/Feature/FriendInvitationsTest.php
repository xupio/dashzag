<?php

use App\Mail\FriendInvitationMail;
use App\Models\FriendInvitation;
use App\Models\User;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('user can resend email for pending friend invitation', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $friendInvitation = FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Pending Friend',
        'email' => 'pending-friend@example.com',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.friends.resend', $friendInvitation));

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHas('invite_success');

    Mail::assertSent(FriendInvitationMail::class, function (FriendInvitationMail $mail) use ($friendInvitation) {
        return $mail->friendInvitation->is($friendInvitation)
            && $mail->hasTo('pending-friend@example.com');
    });
});

test('user cannot resend someone else pending friend invitation', function () {
    Mail::fake();

    $owner = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $friendInvitation = FriendInvitation::create([
        'user_id' => $owner->id,
        'name' => 'Pending Friend',
        'email' => 'pending-friend@example.com',
    ]);

    $this->actingAs($otherUser)
        ->post(route('dashboard.friends.resend', $friendInvitation))
        ->assertForbidden();

    Mail::assertNothingSent();
});

