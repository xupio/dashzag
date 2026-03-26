<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityFeedNotification extends Notification
{
    use Queueable;

    public function __construct(protected array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        $category = $this->categoryFor($notifiable);
        $channels = method_exists($notifiable, 'notificationChannelsFor')
            ? $notifiable->notificationChannelsFor($category)
            : ['database'];

        if (($this->payload['force_mail'] ?? false) && ! in_array('mail', $channels, true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);
        $message = (new MailMessage)
            ->subject('ZagChain: '.$data['subject'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line($data['message']);

        if ($data['context_label'] || $data['context_value']) {
            $message->line(trim(($data['context_label'] ? $data['context_label'].': ' : '').($data['context_value'] ?? '')));
        }

        if (! is_null($data['amount'])) {
            $message->line(($data['amount_label'] ?? 'Amount').': $'.number_format((float) $data['amount'], 2));
        }

        if ($data['status_line']) {
            $message->line($data['status_line']);
        }

        if ($data['notes_line']) {
            $message->line($data['notes_line']);
        }

        return $message->action('Open ZagChain Dashboard', route('dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'category' => 'activity',
            'status' => 'info',
            'subject' => 'Account activity',
            'message' => 'A new activity update is available.',
            'status_line' => null,
            'notes_line' => null,
            'context_label' => 'Dashboard activity',
            'context_value' => null,
            'amount' => null,
            'amount_label' => null,
            'investment_id' => null,
            'related_user_id' => null,
        ], $this->payload, [
            'category' => $this->categoryFor($notifiable),
        ]);
    }

    protected function categoryFor(object $notifiable): string
    {
        $category = $this->payload['category'] ?? 'activity';

        return method_exists($notifiable, 'normalizeNotificationCategory')
            ? $notifiable->normalizeNotificationCategory($category)
            : $category;
    }
}
