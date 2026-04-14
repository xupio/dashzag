<?php

namespace App\Support;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

class N8nWebhook
{
    public static function sendUserRegistered(User $user): void
    {
        self::dispatch('user.registered', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'sponsor_user_id' => $user->sponsor_user_id,
                'email_verified' => $user->hasVerifiedEmail(),
                'created_at' => optional($user->created_at)?->toIso8601String(),
            ],
        ]);
    }

    public static function sendKycSubmitted(User $user): void
    {
        self::dispatch('kyc.submitted', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
            ],
            'kyc' => [
                'status' => $user->kyc_status,
                'proof_original_name' => $user->kyc_proof_original_name,
                'submitted_at' => optional($user->kyc_submitted_at)?->toIso8601String(),
            ],
        ]);
    }

    public static function sendPayoutRequested(PayoutRequest $payoutRequest): void
    {
        $payoutRequest->loadMissing('user');

        self::dispatch('payout.requested', [
            'payout' => [
                'id' => $payoutRequest->id,
                'amount' => (float) $payoutRequest->amount,
                'fee_amount' => (float) $payoutRequest->fee_amount,
                'net_amount' => (float) $payoutRequest->net_amount,
                'fee_rate' => (float) $payoutRequest->fee_rate,
                'method' => $payoutRequest->method,
                'destination' => $payoutRequest->destination,
                'notes' => $payoutRequest->notes,
                'status' => $payoutRequest->status,
                'requested_at' => optional($payoutRequest->requested_at)?->toIso8601String(),
            ],
            'user' => [
                'id' => $payoutRequest->user?->id,
                'name' => $payoutRequest->user?->name,
                'email' => $payoutRequest->user?->email,
                'account_type' => $payoutRequest->user?->account_type,
            ],
        ]);
    }

    public static function dispatch(string $event, array $payload): void
    {
        $url = (string) config('services.n8n.webhook_url');
        $enabled = (bool) config('services.n8n.enabled');

        if (! $enabled || blank($url)) {
            return;
        }

        $secret = (string) config('services.n8n.webhook_secret');

        try {
            Http::acceptJson()
                ->asJson()
                ->timeout(5)
                ->withHeaders([
                    'X-ZagChain-Event' => $event,
                    'X-ZagChain-Webhook-Secret' => $secret,
                ])
                ->post($url, [
                    'event' => $event,
                    'occurred_at' => now()->toIso8601String(),
                    'app' => [
                        'name' => config('app.name'),
                        'env' => config('app.env'),
                        'url' => config('app.url'),
                    ],
                    'data' => $payload,
                ])
                ->throw();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
