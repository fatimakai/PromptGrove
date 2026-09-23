<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class RazorpaySubscriptionGateway
{
    public function configured(): bool
    {
        return str_starts_with((string) config('billing.razorpay.key_id'), 'rzp_test_')
            && filled(config('billing.razorpay.key_secret'))
            && preg_match('/^plan_[A-Za-z0-9]{14}$/', (string) config('billing.razorpay.plan_id')) === 1;
    }

    public function create(User $user): array
    {
        $this->ensureConfigured();

        return $this->client()->post('/subscriptions', [
            'plan_id' => config('billing.razorpay.plan_id'),
            'total_count' => max(1, (int) config('billing.razorpay.total_count')),
            'quantity' => 1,
            'customer_notify' => true,
            'notes' => ['promptgrove_user_id' => (string) $user->id],
        ])->throw()->json();
    }

    public function fetch(string $subscriptionId): array
    {
        $this->ensureConfigured();

        return $this->client()->get('/subscriptions/'.rawurlencode($subscriptionId))->throw()->json();
    }

    public function cancel(string $subscriptionId): array
    {
        $this->ensureConfigured();

        return $this->client()->post('/subscriptions/'.rawurlencode($subscriptionId).'/cancel', [
            'cancel_at_cycle_end' => false,
        ])->throw()->json();
    }

    public function verifyCheckout(string $paymentId, string $subscriptionId, string $signature): bool
    {
        if (! $this->configured() || ! preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $paymentId.'|'.$subscriptionId, (string) config('billing.razorpay.key_secret'));

        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('billing.razorpay.webhook_secret');

        return $secret !== '' && is_string($signature) && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('billing.razorpay.base_url'))
            ->withBasicAuth((string) config('billing.razorpay.key_id'), (string) config('billing.razorpay.key_secret'))
            ->acceptJson()->asJson()->timeout(15);
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new BillingException('Razorpay sandbox is not configured. Add a test key and plan ID.', 503);
        }
    }
}
