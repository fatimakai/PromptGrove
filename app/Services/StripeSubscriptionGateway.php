<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeSubscriptionGateway
{
    public function enabled(): bool
    {
        return (bool) config('billing.stripe.enabled');
    }

    public function configured(): bool
    {
        return $this->enabled()
            && str_starts_with((string) config('billing.stripe.secret_key'), 'sk_test_')
            && str_starts_with((string) config('billing.stripe.price_id'), 'price_');
    }

    public function webhookConfigured(): bool
    {
        return $this->configured()
            && str_starts_with((string) config('billing.stripe.webhook_secret'), 'whsec_');
    }

    /** @return array{id: string, url: string} */
    public function createCheckout(User $user): array
    {
        $this->ensureConfigured();

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'success_url' => route('billing.stripe.return').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.index', ['cancelled' => 1]),
            'payment_method_collection' => 'always',
            'billing_address_collection' => 'required',
            'line_items' => [[
                'price' => config('billing.stripe.price_id'),
                'quantity' => 1,
            ]],
            'metadata' => ['promptgrove_user_id' => (string) $user->id],
            'subscription_data' => [
                'metadata' => ['promptgrove_user_id' => (string) $user->id],
            ],
        ], [
            'idempotency_key' => hash('sha256', implode(':', [
                'promptgrove-checkout',
                $user->id,
                config('billing.stripe.price_id'),
                intdiv(now()->timestamp, 300),
            ])),
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new BillingException('Stripe did not return a Checkout URL. Please try again.');
        }

        $host = parse_url($session->url, PHP_URL_HOST);
        if ($host !== 'checkout.stripe.com') {
            throw new BillingException('Stripe returned an invalid Checkout URL. Please try again.');
        }

        return ['id' => $session->id, 'url' => $session->url];
    }

    /** @return array<string, mixed> */
    public function fetchCheckout(string $sessionId): array
    {
        $this->ensureConfigured();

        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        return $this->normalizeCheckout($session);
    }

    /** @return array<string, mixed> */
    public function fetchSubscription(string $subscriptionId): array
    {
        $this->ensureConfigured();

        return $this->normalizeSubscription(
            $this->client()->subscriptions->retrieve($subscriptionId),
        );
    }

    /** @return array<string, mixed> */
    public function cancel(string $subscriptionId): array
    {
        $this->ensureConfigured();

        return $this->normalizeSubscription(
            $this->client()->subscriptions->cancel($subscriptionId, [
                'invoice_now' => false,
                'prorate' => false,
            ]),
        );
    }

    /**
     * @return array{id: string, type: string, subscription_id: ?string, user_id: ?string}|null
     */
    public function verifyWebhook(string $rawBody, ?string $signature): ?array
    {
        if (! $this->webhookConfigured() || ! is_string($signature) || $signature === '') {
            return null;
        }

        try {
            $event = Webhook::constructEvent(
                $rawBody,
                $signature,
                (string) config('billing.stripe.webhook_secret'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return null;
        }

        return $this->normalizeEvent($event);
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) config('billing.stripe.secret_key'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->enabled()) {
            throw new BillingException('Stripe test checkout is disabled in this deployment.', 503);
        }

        if (! $this->configured()) {
            throw new BillingException('Stripe test mode is not configured. Add a test secret key and recurring Price ID.', 503);
        }
    }

    /** @return array<string, mixed> */
    private function normalizeCheckout(Session $session): array
    {
        $subscription = $session->subscription;
        $subscriptionId = $subscription instanceof StripeSubscription
            ? $subscription->id
            : (is_string($subscription) ? $subscription : null);

        return [
            'id' => $session->id,
            'status' => $session->status,
            'mode' => $session->mode,
            'client_reference_id' => $session->client_reference_id,
            'user_id' => $session->metadata?->promptgrove_user_id,
            'subscription_id' => $subscriptionId,
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeSubscription(StripeSubscription $subscription): array
    {
        $item = $subscription->items->data[0] ?? null;
        if ($item === null || ! is_string($item->price?->id)) {
            throw new BillingException('Stripe returned a subscription without a recurring Price.', 422);
        }

        return [
            'id' => $subscription->id,
            'plan_id' => $item->price->id,
            'status' => $subscription->status,
            'current_start' => $item->current_period_start,
            'current_end' => $item->current_period_end,
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'customer_id' => is_string($subscription->customer) ? $subscription->customer : $subscription->customer?->id,
            'promptgrove_user_id' => $subscription->metadata?->promptgrove_user_id,
        ];
    }

    /** @return array{id: string, type: string, subscription_id: ?string, user_id: ?string} */
    private function normalizeEvent(Event $event): array
    {
        $object = $event->data->object;
        $payload = $object->toArray();
        $objectType = $payload['object'] ?? null;
        $subscriptionId = null;
        $userId = null;

        if ($objectType === 'subscription') {
            $subscriptionId = $this->resourceId($payload);
            $userId = data_get($payload, 'metadata.promptgrove_user_id');
        } elseif ($objectType === 'checkout.session') {
            $subscriptionId = $this->resourceId(data_get($payload, 'subscription'));
            $userId = data_get($payload, 'client_reference_id')
                ?: data_get($payload, 'metadata.promptgrove_user_id');
        } else {
            $subscriptionId = $this->resourceId(
                data_get($payload, 'parent.subscription_details.subscription')
                    ?? data_get($payload, 'subscription'),
            );
        }

        return [
            'id' => $event->id,
            'type' => $event->type,
            'subscription_id' => $subscriptionId,
            'user_id' => is_string($userId) ? $userId : null,
        ];
    }

    private function resourceId(mixed $resource): ?string
    {
        if (is_string($resource)) {
            return $resource;
        }

        return is_array($resource) && is_string($resource['id'] ?? null)
            ? $resource['id']
            : null;
    }
}
