<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PayPalSubscriptionGateway;
use App\Services\RazorpaySubscriptionGateway;
use App\Services\StripeSubscriptionGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class BillingWebhookController extends Controller
{
    public function razorpay(Request $request, RazorpaySubscriptionGateway $gateway, SubscriptionService $subscriptions): JsonResponse
    {
        $raw = $request->getContent();
        abort_unless($gateway->verifyWebhook($raw, $request->header('X-Razorpay-Signature')), 401);
        $payload = $request->json()->all();

        return $this->process(
            Subscription::PROVIDER_RAZORPAY,
            (string) ($payload['id'] ?? hash('sha256', $raw)),
            (string) ($payload['event'] ?? 'unknown'),
            $payload,
            $payload['payload']['subscription']['entity'] ?? null,
            $subscriptions,
        );
    }

    public function paypal(Request $request, PayPalSubscriptionGateway $gateway, SubscriptionService $subscriptions): JsonResponse
    {
        $payload = $request->json()->all();
        abort_unless($gateway->verifyWebhook($request, $payload), 401);

        return $this->process(
            Subscription::PROVIDER_PAYPAL,
            (string) ($payload['id'] ?? hash('sha256', $request->getContent())),
            (string) ($payload['event_type'] ?? 'unknown'),
            $payload,
            str_starts_with((string) ($payload['event_type'] ?? ''), 'BILLING.SUBSCRIPTION.') ? ($payload['resource'] ?? null) : null,
            $subscriptions,
        );
    }

    public function stripe(Request $request, StripeSubscriptionGateway $gateway, SubscriptionService $subscriptions): JsonResponse
    {
        $event = $gateway->verifyWebhook($request->getContent(), $request->header('Stripe-Signature'));
        abort_unless(is_array($event), 401);

        $storedEvent = PaymentWebhookEvent::firstOrCreate(
            ['provider' => Subscription::PROVIDER_STRIPE, 'event_id' => $event['id']],
            ['event_type' => $event['type'], 'payload' => $request->json()->all()],
        );

        if (! $storedEvent->wasRecentlyCreated && $storedEvent->status === 'processed') {
            return response()->json(['received' => true]);
        }

        try {
            $supportedTypes = [
                'checkout.session.completed',
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'invoice.paid',
                'invoice.payment_failed',
            ];
            $subscriptionId = $event['subscription_id'];

            if (in_array($event['type'], $supportedTypes, true) && is_string($subscriptionId)) {
                $remote = $gateway->fetchSubscription($subscriptionId);
                $subscription = Subscription::query()
                    ->where('provider', Subscription::PROVIDER_STRIPE)
                    ->where('provider_subscription_id', $subscriptionId)
                    ->first();

                if ($subscription) {
                    $subscriptions->sync($subscription, $remote);
                } else {
                    $userId = $event['user_id'] ?? $remote['promptgrove_user_id'] ?? null;
                    $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

                    if ($user) {
                        $subscriptions->createLocal($user, Subscription::PROVIDER_STRIPE, $remote);
                    }
                }
            }

            $storedEvent->update(['status' => 'processed', 'processed_at' => now(), 'failure_reason' => null]);

            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);
            $storedEvent->update(['status' => 'failed', 'failure_reason' => 'Webhook processing failed.']);

            return response()->json(['received' => false], 500);
        }
    }

    private function process(
        string $provider,
        string $eventId,
        string $eventType,
        array $payload,
        mixed $remoteSubscription,
        SubscriptionService $subscriptions,
    ): JsonResponse {
        $event = PaymentWebhookEvent::firstOrCreate(
            ['provider' => $provider, 'event_id' => $eventId],
            ['event_type' => $eventType, 'payload' => $payload],
        );

        if (! $event->wasRecentlyCreated && $event->status === 'processed') {
            return response()->json(['received' => true]);
        }

        try {
            if (is_array($remoteSubscription) && is_string($remoteSubscription['id'] ?? null)) {
                $subscription = Subscription::query()
                    ->where('provider', $provider)
                    ->where('provider_subscription_id', $remoteSubscription['id'])->first();

                if ($subscription) {
                    $subscriptions->sync($subscription, $remoteSubscription);
                }
            }

            $event->update(['status' => 'processed', 'processed_at' => now(), 'failure_reason' => null]);

            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['status' => 'failed', 'failure_reason' => 'Webhook processing failed.']);

            return response()->json(['received' => false], 500);
        }
    }
}
