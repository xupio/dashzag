<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use App\Support\MiningPlatform;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected PayoutRequest $payoutRequest,
        protected string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return method_exists($notifiable, 'notificationChannelsFor')
            ? $notifiable->notificationChannelsFor('payout')
            : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->payoutRequest->fresh() ?? $this->payoutRequest;

        return (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->introLine())
            ->line('Gross amount: $'.number_format((float) $request->amount, 2))
            ->line('Fee amount: $'.number_format((float) $request->fee_amount, 2))
            ->line('Net amount: $'.number_format((float) $request->net_amount, 2))
            ->line('Method: '.$this->methodLabel($request))
            ->line('Destination: '.$request->destination)
            ->line($this->statusLine($request))
            ->line($this->notesLine($request));
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->payoutRequest->fresh() ?? $this->payoutRequest;

        return [
            'category' => 'payout',
            'payout_request_id' => $request->id,
            'status' => $this->status,
            'subject' => $this->subject(),
            'message' => $this->introLine(),
            'status_line' => $this->statusLine($request),
            'notes_line' => $this->notesLine($request),
            'gross_amount' => (float) $request->amount,
            'fee_amount' => (float) $request->fee_amount,
            'net_amount' => (float) $request->net_amount,
            'method' => $request->method,
            'method_label' => $this->methodLabel($request),
            'destination' => $request->destination,
            'transaction_reference' => $request->transaction_reference,
            'requested_at' => optional($request->requested_at)?->toIso8601String(),
            'approved_at' => optional($request->approved_at)?->toIso8601String(),
            'processed_at' => optional($request->processed_at)?->toIso8601String(),
        ];
    }

    protected function subject(): string
    {
        return MiningPlatform::renderNotificationTemplate(
            MiningPlatform::notificationTemplateSetting('template_payout_'.$this->status.'_subject'),
            $this->templateReplacements(),
        );
    }

    protected function introLine(): string
    {
        return MiningPlatform::renderNotificationTemplate(
            MiningPlatform::notificationTemplateSetting('template_payout_'.$this->status.'_message'),
            $this->templateReplacements(),
        );
    }

    protected function templateReplacements(): array
    {
        $request = $this->payoutRequest->fresh() ?? $this->payoutRequest;

        return [
            'gross_amount' => number_format((float) $request->amount, 2),
            'fee_amount' => number_format((float) $request->fee_amount, 2),
            'net_amount' => number_format((float) $request->net_amount, 2),
            'method_label' => $this->methodLabel($request),
            'destination' => $request->destination,
        ];
    }

    protected function statusLine(PayoutRequest $request): string
    {
        return match ($this->status) {
            'submitted' => 'Requested at: '.optional($request->requested_at)->format('M d, Y h:i A'),
            'approved' => 'Approved at: '.(optional($request->approved_at)->format('M d, Y h:i A') ?? 'Pending'),
            'paid' => 'Paid at: '.(optional($request->processed_at)->format('M d, Y h:i A') ?? 'Pending'),
            default => 'Current status: '.str($request->status)->title(),
        };
    }

    protected function notesLine(PayoutRequest $request): string
    {
        if ($this->status === 'paid' && $request->transaction_reference) {
            return 'Transaction reference: '.$request->transaction_reference;
        }

        if ($request->admin_notes) {
            return 'Admin notes: '.$request->admin_notes;
        }

        if ($request->notes) {
            return 'Your note: '.$request->notes;
        }

        return 'We will keep you informed about further payout updates.';
    }

    protected function methodLabel(PayoutRequest $request): string
    {
        return MiningPlatform::payoutMethodLabel($request->method);
    }
}