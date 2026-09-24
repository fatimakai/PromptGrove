<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;

class SubscriptionService
{
    public function createLocal(User $user, string $provider, array $remote): Subscription
    {
        $id = $remote['id'] ?? null;
        $planId = $remote['plan_id'] ?? null;

        if (! is_string($id) || ! is_string($planId)) {
            throw new BillingException('The payment provider returned an incomplete subscription.');
        }

        $expectedPlan = $provider === Subscription::PROVIDER_STRIPE
            ? config('billing.stripe.price_id')
            : config("billing.{$provider}.plan_id");
        if (! is_string($expectedPlan) || ! hash_equals($expectedPlan, $planId)) {
            throw new BillingException('The payment provider returned an unexpected plan.', 422);
        }

        $existing = Subscription::query()
            ->where('provider', $provider)
            ->where('provider_subscription_id', $id)
            ->first();
        if ($existing && $existing->user_id !== $user->id) {
            throw new BillingException('That provider subscription is already linked to another account.', 422);
        }

        return Subscription::updateOrCreate(
            ['provider' => $provider, 'provider_subscription_id' => $id],
            [
                'user_id' => $user->id,
                'provider_plan_id' => $planId,
                'status' => $this->normalizeStatus($remote['status'] ?? 'created'),
                'amount' => config('billing.plan.amount'),
                'currency' => config('billing.plan.currency'),
                'current_period_start' => $this->periodDate($provider, $remote, 'start'),
                'current_period_end' => $this->periodDate($provider, $remote, 'end'),
                'last_synced_at' => now(),
                'metadata' => $this->safeMetadata($remote),
            ],
        );
    }

    public function sync(Subscription $subscription, array $remote): Subscription
    {
        if (($remote['id'] ?? null) !== $subscription->provider_subscription_id
            || ($remote['plan_id'] ?? null) !== $subscription->provider_plan_id) {
            throw new BillingException('Subscription identity verification failed.', 422);
        }

        $status = $this->normalizeStatus($remote['status'] ?? $subscription->status);
        $periodStart = $this->periodDate($subscription->provider, $remote, 'start');
        $periodEnd = $this->periodDate($subscription->provider, $remote, 'end');
        $subscription->update([
            'status' => $status,
            'current_period_start' => $periodStart ?? $subscription->current_period_start,
            'current_period_end' => $periodEnd ?? $subscription->current_period_end,
            'cancelled_at' => $status === 'cancelled' ? ($subscription->cancelled_at ?? now()) : $subscription->cancelled_at,
            'last_synced_at' => now(),
            'metadata' => $this->safeMetadata($remote),
        ]);

        return $subscription->refresh();
    }

    private function normalizeStatus(mixed $status): string
    {
        return match (strtolower((string) $status)) {
            'active' => Subscription::STATUS_ACTIVE,
            'cancelled', 'canceled' => 'cancelled',
            'suspended', 'halted', 'pending' => strtolower((string) $status),
            'expired', 'completed' => strtolower((string) $status),
            'trialing', 'past_due', 'incomplete', 'incomplete_expired', 'unpaid', 'paused' => strtolower((string) $status),
            'approved', 'authenticated' => 'approved',
            default => 'created',
        };
    }

    private function periodDate(string $provider, array $remote, string $boundary): ?CarbonImmutable
    {
        if (in_array($provider, [Subscription::PROVIDER_RAZORPAY, Subscription::PROVIDER_STRIPE], true)) {
            $value = $remote[$boundary === 'start' ? 'current_start' : 'current_end'] ?? null;

            return is_numeric($value) && (int) $value > 0 ? CarbonImmutable::createFromTimestamp((int) $value) : null;
        }

        $value = $boundary === 'start'
            ? ($remote['start_time'] ?? null)
            : ($remote['billing_info']['next_billing_time'] ?? null);

        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private function safeMetadata(array $remote): array
    {
        return collect($remote)->only([
            'id', 'status', 'plan_id', 'quantity', 'start_time', 'current_start', 'current_end',
            'charge_at', 'paid_count', 'remaining_count', 'short_url',
            'cancel_at_period_end', 'customer_id', 'promptgrove_user_id',
        ])->all();
    }
}
