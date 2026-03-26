<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminHealthSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected array $summary,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Daily admin health summary')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Here is your daily ZagChain admin health summary.')
            ->line('Pending investment orders: '.$this->summary['pending_investment_orders'])
            ->line('Pending payout requests: '.$this->summary['pending_payout_requests'])
            ->line('Pending orders with proof: '.$this->summary['pending_orders_with_proof'])
            ->line('Pending orders missing proof: '.$this->summary['pending_orders_missing_proof'])
            ->line('Stale pending investments: '.$this->summary['stale_pending_investments'])
            ->line('Stale pending payouts: '.$this->summary['stale_pending_payouts'])
            ->line('Recent admin actions: '.$this->summary['recent_admin_actions'])
            ->line('Pending invitations: '.$this->summary['pending_friend_invitations']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'admin',
            'status' => 'info',
            'subject' => 'Daily admin health summary',
            'message' => 'Your daily admin operations snapshot is ready.',
            'context_label' => 'Summary period',
            'context_value' => $this->summary['period_label'] ?? 'the last 24 hours',
            'status_line' => 'Pending investments: '.$this->summary['pending_investment_orders'].' | Pending payouts: '.$this->summary['pending_payout_requests'],
            'notes_line' => 'Proof ready: '.$this->summary['pending_orders_with_proof'].' | Missing proof: '.$this->summary['pending_orders_missing_proof'].' | Stale items: '.($this->summary['stale_pending_investments'] + $this->summary['stale_pending_payouts']),
            'admin_health_summary' => $this->summary,
        ];
    }
}
