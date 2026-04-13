<?php

use App\Mail\FriendInvitationMail;
use App\Models\FriendInvitation;
use App\Models\User;
use App\Support\MiningPlatform;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    MiningPlatform::ensureDefaults();
});

test('friends page shows share and invite toolkit', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.friends'));

    $response->assertOk();
    $response->assertSee('Share & invite', false);
    $response->assertSeeText('Copy how it works link');
    $response->assertSeeText('Copy register link');
    $response->assertSeeText('WhatsApp message');
    $response->assertSeeText('Telegram message');
    $response->assertSeeText('Referral performance');
    $response->assertSeeText('Pending invitations');
    $response->assertSeeText('Verified friends');
    $response->assertSeeText('Registered users');
    $response->assertSeeText('Active investors');
    $response->assertSeeText('Next referral target');
    $response->assertSeeText('Progress');
    $response->assertSeeText('Best message tips');
    $response->assertSeeText('Invite warm contacts first');
    $response->assertSeeText('Send “How it works” before register');
    $response->assertSeeText('Follow up after verification');
});

test('friends page shows follow up actions for verified and registered invitations', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Verified Friend',
        'email' => 'verified-friend@example.com',
        'verified_at' => now(),
    ]);

    FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Registered Friend',
        'email' => 'registered-friend@example.com',
        'verified_at' => now()->subDay(),
        'registered_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.friends'));

    $response->assertOk();
    $response->assertSeeText('Copy follow-up');
    $response->assertSeeText('Registered');
    $response->assertSeeText('Verified');
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

test('user can invite a friend with required name email and country', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.friends.invite'), [
        'name' => 'New Friend',
        'email' => 'new-friend@example.com',
        'phone' => '',
        'country' => 'United Arab Emirates',
    ]);

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHas('invite_success');

    $this->assertDatabaseHas('friend_invitations', [
        'user_id' => $user->id,
        'name' => 'New Friend',
        'email' => 'new-friend@example.com',
        'phone' => null,
        'country' => 'United Arab Emirates',
    ]);

    Mail::assertSent(FriendInvitationMail::class, function (FriendInvitationMail $mail) {
        return $mail->hasTo('new-friend@example.com');
    });
});

test('user can invite a friend with uppercase email and it is normalized to lowercase', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.friends.invite'), [
        'name' => 'New Friend',
        'email' => 'New.Friend@Example.COM',
        'phone' => '',
        'country' => 'United Arab Emirates',
    ]);

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHas('invite_success');

    $this->assertDatabaseHas('friend_invitations', [
        'user_id' => $user->id,
        'email' => 'new.friend@example.com',
    ]);

    Mail::assertSent(FriendInvitationMail::class, function (FriendInvitationMail $mail) {
        return $mail->hasTo('new.friend@example.com');
    });
});

test('user invite phone is trimmed before storing', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)->post(route('dashboard.friends.invite'), [
        'name' => 'Phone Friend',
        'email' => 'phone-friend@example.com',
        'phone' => '  +971 50 123 4567  ',
        'country' => 'United Arab Emirates',
    ])->assertRedirect(route('dashboard.friends'));

    $this->assertDatabaseHas('friend_invitations', [
        'user_id' => $user->id,
        'email' => 'phone-friend@example.com',
        'phone' => '+971 50 123 4567',
    ]);
});

test('user must choose a valid country when inviting a friend', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->from(route('dashboard.friends'))
        ->actingAs($user)
        ->post(route('dashboard.friends.invite'), [
            'name' => 'New Friend',
            'email' => 'new-friend@example.com',
            'phone' => '',
            'country' => 'Atlantis',
        ]);

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHasErrors('country');

    $this->assertDatabaseMissing('friend_invitations', [
        'user_id' => $user->id,
        'email' => 'new-friend@example.com',
    ]);

    Mail::assertNothingSent();
});

test('friend invitation email uses zagchain branding and copy', function () {
    $inviter = User::factory()->create([
        'name' => 'Mohammad',
        'email_verified_at' => now(),
    ]);

    $friendInvitation = FriendInvitation::create([
        'user_id' => $inviter->id,
        'name' => 'Future Investor',
        'email' => 'future-investor@example.com',
    ]);

    $mail = new FriendInvitationMail(
        $friendInvitation,
        $inviter,
        'https://example.com/verify-friend'
    );

    expect($mail->envelope()->subject)->toBe('Mohammad invited you to join ZagChain');

    $rendered = $mail->render();

    expect($rendered)
        ->toContain('invited you to join us with ZagChain.')
        ->toContain('branding/zagchain-logo.png');
});

test('user cannot send more than the daily friend invitation email limit', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->post(route('dashboard.friends.invite'), [
            'name' => 'New Friend '.$attempt,
            'email' => 'new-friend-'.$attempt.'@example.com',
            'phone' => '',
            'country' => 'United Arab Emirates',
        ])->assertRedirect(route('dashboard.friends'));
    }

    $response = $this->from(route('dashboard.friends'))
        ->actingAs($user)
        ->post(route('dashboard.friends.invite'), [
            'name' => 'New Friend',
            'email' => 'new-friend@example.com',
            'phone' => '',
            'country' => 'United Arab Emirates',
        ]);

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHas('invite_limit');

    Mail::assertSent(FriendInvitationMail::class, 10);
});

test('user cannot resend more than the daily friend invitation email limit', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $friendInvitation = FriendInvitation::create([
        'user_id' => $user->id,
        'name' => 'Pending Friend',
        'email' => 'pending-friend@example.com',
    ]);

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)
            ->post(route('dashboard.friends.resend', $friendInvitation))
            ->assertRedirect(route('dashboard.friends'));
    }

    $response = $this->from(route('dashboard.friends'))
        ->actingAs($user)
        ->post(route('dashboard.friends.resend', $friendInvitation));

    $response->assertRedirect(route('dashboard.friends'));
    $response->assertSessionHas('invite_limit');

    Mail::assertSent(FriendInvitationMail::class, 10);
});

