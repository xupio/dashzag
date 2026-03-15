<?php

use App\Models\InternalMessage;
use App\Models\InternalMessageRecipient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        'mail_action' => 'send',
    ]);

    $response->assertRedirect(route('email.sent'));

    $message = InternalMessage::query()->where('subject', 'Welcome to ZagChain')->first();

    expect($message)->not->toBeNull();
    expect($message->sender_id)->toBe($sender->id);
    expect($message->is_draft)->toBeFalse();
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

test('user can save and later send a draft message', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'to' => [$recipient->id],
            'subject' => 'Draft offer',
            'body' => 'Initial draft body.',
            'mail_action' => 'draft',
        ])
        ->assertRedirect();

    $draft = InternalMessage::query()->where('sender_id', $sender->id)->where('is_draft', true)->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_to)->toBe([$recipient->id]);
    expect($draft->recipients()->count())->toBe(0);

    $this->actingAs($sender)
        ->get(route('email.drafts'))
        ->assertOk()
        ->assertSee('Draft offer');

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'draft_id' => $draft->id,
            'to' => [$recipient->id],
            'subject' => 'Draft offer',
            'body' => 'Final sent body.',
            'mail_action' => 'send',
        ])
        ->assertRedirect(route('email.sent'));

    expect($draft->fresh()->is_draft)->toBeFalse();
    expect($draft->fresh()->recipients()->count())->toBe(1);

    $this->actingAs($sender)
        ->get(route('email.sent'))
        ->assertOk()
        ->assertSee('Draft offer');
});

test('user can upload attachment on draft and download it after sending', function () {
    Storage::fake('local');

    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);
    $file = UploadedFile::fake()->create('proposal.pdf', 256, 'application/pdf');

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'to' => [$recipient->id],
            'subject' => 'Attachment draft',
            'body' => 'Please review the file.',
            'mail_action' => 'draft',
            'attachments' => [$file],
        ])
        ->assertRedirect();

    $draft = InternalMessage::query()->where('sender_id', $sender->id)->where('is_draft', true)->first();
    $attachment = $draft->attachments()->first();

    expect($attachment)->not->toBeNull();
    Storage::disk('local')->assertExists($attachment->storage_path);

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'draft_id' => $draft->id,
            'to' => [$recipient->id],
            'subject' => 'Attachment draft',
            'body' => 'Please review the file.',
            'mail_action' => 'send',
        ])
        ->assertRedirect(route('email.sent'));

    expect($draft->fresh()->is_draft)->toBeFalse();
    expect($draft->fresh()->attachments()->count())->toBe(1);

    $recipientRecord = $draft->fresh()->recipients()->where('user_id', $recipient->id)->first();

    $this->actingAs($recipient)
        ->get(route('email.read', $recipientRecord))
        ->assertOk()
        ->assertSee('proposal.pdf');

    $this->actingAs($recipient)
        ->get(route('email.attachments.download', $attachment))
        ->assertOk();
});

test('user can remove an attachment from a draft before sending', function () {
    Storage::fake('local');

    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'to' => [$recipient->id],
            'subject' => 'Draft cleanup',
            'body' => 'Remove the wrong file.',
            'mail_action' => 'draft',
            'attachments' => [UploadedFile::fake()->create('wrong-file.pdf', 64, 'application/pdf')],
        ])
        ->assertRedirect();

    $draft = InternalMessage::query()->where('sender_id', $sender->id)->where('is_draft', true)->first();
    $attachment = $draft->attachments()->first();

    Storage::disk('local')->assertExists($attachment->storage_path);

    $this->actingAs($sender)
        ->post(route('email.draft-attachments.remove', [$draft, $attachment]))
        ->assertRedirect(route('email.compose', ['draft' => $draft->id]));

    expect($draft->fresh()->attachments()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->storage_path);
});

test('user can delete a draft and all of its attachments', function () {
    Storage::fake('local');

    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($sender)
        ->post(route('email.store'), [
            'to' => [$recipient->id],
            'subject' => 'Discard me',
            'body' => 'This draft should be removed.',
            'mail_action' => 'draft',
            'attachments' => [UploadedFile::fake()->create('discard.pdf', 32, 'application/pdf')],
        ])
        ->assertRedirect();

    $draft = InternalMessage::query()->where('sender_id', $sender->id)->where('is_draft', true)->first();
    $attachment = $draft->attachments()->first();

    Storage::disk('local')->assertExists($attachment->storage_path);

    $this->actingAs($sender)
        ->post(route('email.drafts.delete', $draft))
        ->assertRedirect(route('email.drafts'));

    expect(InternalMessage::query()->whereKey($draft->id)->exists())->toBeFalse();
    expect(InternalMessage::query()->where('sender_id', $sender->id)->where('is_draft', true)->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->storage_path);
});

test('user can delete a received message from mailbox', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);
    $otherRecipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Mailbox delete',
        'body' => 'Delete this from one mailbox only.',
    ]);

    $recipientRecord = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $otherRecord = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $otherRecipient->id,
        'recipient_type' => 'cc',
    ]);

    $this->actingAs($recipient)
        ->post(route('email.delete', $recipientRecord))
        ->assertRedirect(route('email.trash'));

    expect($recipientRecord->fresh()->trashed_at)->not->toBeNull();
    expect(InternalMessageRecipient::query()->whereKey($otherRecord->id)->exists())->toBeTrue();
    expect(InternalMessage::query()->whereKey($message->id)->exists())->toBeTrue();
});
test('user can restore and permanently purge a trashed mailbox message', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $message = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Trash flow',
        'body' => 'Restore me and then delete me.',
    ]);

    $recipientRecord = InternalMessageRecipient::create([
        'internal_message_id' => $message->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
        'trashed_at' => now(),
    ]);

    $this->actingAs($recipient)
        ->get(route('email.trash'))
        ->assertOk()
        ->assertSee('Trash flow');

    $this->actingAs($recipient)
        ->post(route('email.restore', $recipientRecord))
        ->assertRedirect(route('email.trash'));

    expect($recipientRecord->fresh()->trashed_at)->toBeNull();

    $recipientRecord->forceFill(['trashed_at' => now()])->save();

    $this->actingAs($recipient)
        ->post(route('email.purge', $recipientRecord))
        ->assertRedirect(route('email.trash'));

    expect(InternalMessageRecipient::query()->whereKey($recipientRecord->id)->exists())->toBeFalse();
    expect(InternalMessage::query()->whereKey($message->id)->exists())->toBeTrue();
});
test('user can bulk process mailbox messages', function () {
    $sender = User::factory()->create(['email_verified_at' => now()]);
    $recipient = User::factory()->create(['email_verified_at' => now()]);

    $messageOne = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Bulk one',
        'body' => 'First mailbox item.',
    ]);

    $messageTwo = InternalMessage::create([
        'sender_id' => $sender->id,
        'subject' => 'Bulk two',
        'body' => 'Second mailbox item.',
    ]);

    $recordOne = InternalMessageRecipient::create([
        'internal_message_id' => $messageOne->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $recordTwo = InternalMessageRecipient::create([
        'internal_message_id' => $messageTwo->id,
        'user_id' => $recipient->id,
        'recipient_type' => 'to',
    ]);

    $this->actingAs($recipient)
        ->post(route('email.bulk'), [
            'message_ids' => [$recordOne->id],
            'bulk_action' => 'archive',
            'folder' => 'inbox',
        ])
        ->assertRedirect(route('email.inbox'));

    expect($recordOne->fresh()->deleted_at)->not->toBeNull();

    $this->actingAs($recipient)
        ->post(route('email.bulk'), [
            'message_ids' => [$recordTwo->id],
            'bulk_action' => 'trash',
            'folder' => 'inbox',
        ])
        ->assertRedirect(route('email.trash'));

    expect($recordTwo->fresh()->trashed_at)->not->toBeNull();

    $this->actingAs($recipient)
        ->post(route('email.bulk'), [
            'message_ids' => [$recordTwo->id],
            'bulk_action' => 'restore',
            'folder' => 'trash',
        ])
        ->assertRedirect(route('email.trash'));

    expect($recordTwo->fresh()->trashed_at)->toBeNull();

    $recordTwo->forceFill(['trashed_at' => now()])->save();

    $this->actingAs($recipient)
        ->post(route('email.bulk'), [
            'message_ids' => [$recordTwo->id],
            'bulk_action' => 'purge',
            'folder' => 'trash',
        ])
        ->assertRedirect(route('email.trash'));

    expect(InternalMessageRecipient::query()->whereKey($recordTwo->id)->exists())->toBeFalse();
    expect(InternalMessage::query()->whereKey($messageTwo->id)->exists())->toBeTrue();
});
