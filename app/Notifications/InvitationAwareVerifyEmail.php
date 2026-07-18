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
        $inviterSummary = null;

        if ($this->friendInvitations->isNotEmpty()) {
            $inviterNames = $this->friendInvitations
                ->loadMissing('user:id,name')
                ->pluck('user.name')
                ->filter()
                ->unique()
                ->values();

            $inviterSummary = $this->invitationLine($inviterNames);
        }

        return (new MailMessage)
            ->subject('Verify Your ZagChain Email Address')
            ->view('emails.verify-email', [
                'notifiable' => $notifiable,
                'verificationUrl' => $verificationUrl,
                'inviterSummary' => $inviterSummary,
            ]);
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
