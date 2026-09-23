<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalSubscriptionGateway
{
    public function configured(): bool
    {
        return filled(config('billing.paypal.client_id'))
            && filled(config('billing.paypal.client_secret'))
            && filled(config('billing.paypal.plan_id'));
    }

    public function create(User $user): array
    {
        $this->ensureConfigured();

        return $this->client()
            ->withHeader('PayPal-Request-Id', (string) Str::uuid())
            ->post('/v1/billing/subscriptions', [
                'plan_id' => config('billing.paypal.plan_id'),
                'custom_id' => (string) $user->id,
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => route('billing.paypal.return'),
                    'cancel_url' => route('billing.index', ['cancelled' => 1]),
                ],
            ])->throw()->json();
    }

    public function approvalUrl(array $subscription): string
    {
        $url = collect($subscription['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (! is_string($url) || $url === '' || ! in_array($host, ['www.sandbox.paypal.com', 'sandbox.paypal.com'], true)) {
            throw new BillingException('PayPal did not return an approval link. Please try again.');
        }

        return $url;
    }

    public function fetch(string $subscriptionId): array
    {
        $this->ensureConfigured();

        return $this->client()->get('/v1/billing/subscriptions/'.rawurlencode($subscriptionId))->throw()->json();
    }

    public function cancel(string $subscriptionId): void
    {
        $this->ensureConfigured();
        $this->client()->post('/v1/billing/subscriptions/'.rawurlencode($subscriptionId).'/cancel', [
            'reason' => 'Cancelled by the customer in PromptGrove.',
        ])->throw();
    }

    public function verifyWebhook(Request $request, array $event): bool
    {
        if (! $this->configured() || ! filled(config('billing.paypal.webhook_id'))) {
            return false;
        }

        $response = $this->client()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => config('billing.paypal.webhook_id'),
            'webhook_event' => $event,
        ])->throw();

        return $response->json('verification_status') === 'SUCCESS';
    }

    private function client(): PendingRequest
    {
        $token = Http::withBasicAuth(
            (string) config('billing.paypal.client_id'),
            (string) config('billing.paypal.client_secret'),
        )->asForm()->acceptJson()->timeout(15)
            ->post(config('billing.paypal.base_url').'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new BillingException('PayPal sandbox authentication failed.');
        }

        return Http::baseUrl((string) config('billing.paypal.base_url'))
            ->withToken($token)->acceptJson()->asJson()->timeout(15);
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new BillingException('PayPal sandbox is not configured. Add sandbox credentials and a plan ID.', 503);
        }
    }
}
