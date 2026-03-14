<?php

use App\Models\InternalMessage;
use App\Models\InternalMessageRecipient;
use App\Models\User;

beforeEach(function () {
    User::factory()->admin()->create(['email_verified_at' => now()]);
});

test('verified user can send an internal email to another user', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($sender)->post(route('email.store'), [
        'to' => [$recipient->id],
        'subject' => 'Welcome to ZagChain',
        'body' => 'This is an internal platform message.',
    ]);

    $response->assertRedirect(route('email.sent'));

    $message = InternalMessage::query()->where('subject', 'Welcome to ZagChain')->first();

    expect($message)->not->toBeNull();
    expect($message->sender_id)->toBe($sender->id);
    expect($message->recipients()->count())->toBe(1);
});

test('recipient can view a message in inbox and it becomes read', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Internal update',
        'body' => 'Please review the latest ZagChain update.',
    ]);

    $record = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $this->actingAs($recipient)
        ->get(route('email.read', $record))
        ->assertOk()
        ->assertSee('Internal update')
        ->assertSee('Please review the latest ZagChain update.');

    expect($record->fresh()->read_at)->not->toBeNull();
});

test('sender can view sent mailbox', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Sent mailbox test',
        'body' => 'Testing the sent mailbox.',
    ]);

    InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $this->actingAs($sender)
        ->get(route('email.sent'))
        ->assertOk()
        ->assertSee('Sent mailbox test')
        ->assertSee($recipient->name);
});

test('user can reply inside an internal mail thread', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Project rollout',
        'body' => 'Please confirm the rollout window.',
    ]);

    $record = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $this->actingAs($recipient)
        ->post(route('email.reply', $message), [
            'body' => 'Confirmed. We are ready to go live.',
        ])
        ->assertRedirect();

    $reply = InternalMessage::query()->where('reply_to_message_id', $message->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->thread_root_id)->toBe($message->id);
    expect($reply->sender_id)->toBe($recipient->id);
    expect($reply->subject)->toBe('Re: Project rollout');
    expect($reply->recipients()->where('user_id', $sender->id)->exists())->toBeTrue();

    $replyRecipient = $reply->recipients()->where('user_id', $sender->id)->first();

    $this->actingAs($sender)
        ->get(route('email.read', $replyRecipient))
        ->assertOk()
        ->assertSee('Project rollout')
        ->assertSee('Please confirm the rollout window.')
        ->assertSee('Confirmed. We are ready to go live.');

    expect($record->fresh()->read_at)->toBeNull();
});

test('user can star archive and toggle read state for inbox message', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Mailbox actions',
        'body' => 'Testing mailbox actions.',
    ]);

    $record = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
        'read_at' => now(),
    ]);

    $this->actingAs($recipient)
        ->post(route('email.toggle-star', $record))
        ->assertRedirect();

    expect($record->fresh()->starred_at)->not->toBeNull();

    $this->actingAs($recipient)
        ->post(route('email.toggle-read', $record))
        ->assertRedirect();

    expect($record->fresh()->read_at)->toBeNull();

    $this->actingAs($recipient)
        ->post(route('email.archive', $record))
        ->assertRedirect(route('email.inbox'));

    expect($record->fresh()->deleted_at)->not->toBeNull();

    $this->actingAs($recipient)
        ->get(route('email.archived'))
        ->assertOk()
        ->assertSee('Mailbox actions');
});
