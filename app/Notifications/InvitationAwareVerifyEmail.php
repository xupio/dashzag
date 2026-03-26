<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class InvitationAwareVerifyEmail extends VerifyEmail
{
    public function __construct(
        protected Collection $friendInvitations,
    ) {
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        $mailMessage = (new MailMessage)
            ->subject('Verify Your ZagChain Email Address')
            ->greeting('Welcome to ZagChain')
            ->line('Please click the button below to verify your email address and activate your ZagChain account.');

        if ($this->friendInvitations->isNotEmpty()) {
            $inviterNames = $this->friendInvitations
                ->loadMissing('user:id,name')
                ->pluck('user.name')
                ->filter()
                ->unique()
                ->values();

            $mailMessage->line($this->invitationLine($inviterNames));
        }

        return $mailMessage
            ->action('Verify ZagChain Email', $verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function invitationLine(Collection $inviterNames): string
    {
        if ($inviterNames->isEmpty()) {
            return 'You were already invited to join us with ZagChain. Just confirm your email and your invitation status will be updated.';
        }

        if ($inviterNames->count() === 1) {
            return 'You were already invited by '.$inviterNames->first().' to join us with ZagChain. Just confirm your email and your invitation status will be updated.';
        }

        $firstInviter = $inviterNames->first();
        $remainingInvites = $inviterNames->count() - 1;

        return 'You were already invited by '.$firstInviter.' and '.$remainingInvites.' other '.($remainingInvites === 1 ? 'user' : 'users').' to join us with ZagChain. Just confirm your email and your invitation status will be updated.';
    }
}
