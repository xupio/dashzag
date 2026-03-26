<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DigestSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $frequency,
        protected array $summary,
        protected string $periodLabel,
        protected string $source = 'system',
        protected ?int $triggeredById = null,
        protected ?string $triggeredByName = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return method_exists($notifiable, 'notificationChannelsFor')
            ? $notifiable->notificationChannelsFor('digest')
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ZagChain '.ucfirst($this->frequency).' Digest Summary')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Here is your '.$this->frequency.' activity summary for '.$this->periodLabel.'.')
            ->line('Total notifications: '.$this->summary['total'])
            ->line('Payout updates: '.$this->summary['payout'])
            ->line('Rewards: '.$this->summary['reward'])
            ->line('Investments: '.$this->summary['investment'])
            ->line('Network updates: '.$this->summary['network'])
            ->line('Milestones: '.$this->summary['milestone'])
            ->line('Unread remaining: '.$this->summary['unread'])
            ->action('Open ZagChain Notifications', route('dashboard.notifications'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'digest',
            'status' => 'info',
            'subject' => $this->subject(),
            'message' => 'Your '.$this->frequency.' summary for '.$this->periodLabel.' is ready.',
            'context_label' => 'Summary period',
            'context_value' => $this->periodLabel,
            'status_line' => 'Unread remaining: '.$this->summary['unread'],
            'notes_line' => 'Payouts: '.$this->summary['payout'].' | Rewards: '.$this->summary['reward'].' | Investments: '.$this->summary['investment'].' | Network: '.$this->summary['network'].' | Milestones: '.$this->summary['milestone'],
            'amount' => $this->summary['total'],
            'amount_label' => 'Total updates',
            'digest_frequency' => $this->frequency,
            'digest_summary' => $this->summary,
            'digest_source' => $this->source,
            'triggered_by_id' => $this->triggeredById,
            'triggered_by_name' => $this->triggeredByName,
        ];
    }

    protected function subject(): string
    {
        return ucfirst($this->frequency).' digest summary';
    }
}
