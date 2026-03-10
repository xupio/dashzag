<?php

namespace App\Mail;

use App\Models\FriendInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FriendInvitation $friendInvitation,
        public User $inviter,
        public string $verificationUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->inviter->name.' invited you to join',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.friend-invitation',
        );
    }
}
