<?php

namespace App\Services;

use App\Models\InvestmentOrder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ZiinaGatewayService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.ziina.enabled');
    }

    public function requiresWebhookSignature(): bool
    {
        return filled(config('services.ziina.webhook_secret'));
    }

    public function createPaymentIntent(InvestmentOrder $order, string $successUrl, string $cancelUrl, string $failureUrl): array
    {
        $this->guardEnabled();

        $response = $this->request()
            ->post('/payment_intent', [
                'amount' => $this->toBaseUnits((float) $order->amount),
                'currency_code' => (string) config('services.ziina.currency', 'AED'),
                'message' => sprintf('ZagChain %s - Order #%d', $order->package?->name ?? 'investment', $order->id),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'failure_url' => $failureUrl,
                'test' => (bool) config('services.ziina.test_mode', false),
                'expiry' => (string) Carbon::now()->addMinutes((int) config('services.ziina.intent_expiry_minutes', 30))->valueOf(),
                'allow_tips' => (bool) config('services.ziina.allow_tips', false),
            ]);

        return $this->decodeResponse($response);
    }

    public function getPaymentIntent(string $paymentIntentId): array
    {
        $this->guardEnabled();

        $response = $this->request()->get('/payment_intent/'.urlencode($paymentIntentId));

        return $this->decodeResponse($response);
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.ziina.webhook_secret');

        if ($secret === '') {
            return true;
        }

        if (! is_string($signature) || trim($signature) === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, trim($signature));
    }

    public function generateReference(): string
    {
        return 'ZIINA-'.Str::upper(Str::random(10));
    }

    protected function request()
    {
        $token = (string) config('services.ziina.access_token');

        if ($token === '') {
            throw new RuntimeException('Ziina access token is missing.');
        }

        return Http::baseUrl(rtrim((string) config('services.ziina.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout(30);
    }

    protected function decodeResponse(Response $response): array
    {
        if ($response->failed()) {
            throw new RuntimeException('Ziina request failed: '.$response->body());
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new RuntimeException('Ziina returned an unexpected response payload.');
        }

        return $decoded;
    }

    protected function toBaseUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    protected function guardEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Ziina gateway is not enabled.');
        }
    }
}
