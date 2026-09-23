<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingException;
use App\Models\Subscription;
use App\Services\PayPalSubscriptionGateway;
use App\Services\RazorpaySubscriptionGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()->subscriptions()->latest()->get();
        $activeSubscription = $subscriptions->first(fn (Subscription $subscription) => $subscription->grantsProAccess());

        return view('billing.index', [
            'subscriptions' => $subscriptions,
            'activeSubscription' => $activeSubscription,
            'razorpayConfigured' => app(RazorpaySubscriptionGateway::class)->configured(),
            'paypalConfigured' => app(PayPalSubscriptionGateway::class)->configured(),
        ]);
    }

    public function razorpay(Request $request, RazorpaySubscriptionGateway $gateway, SubscriptionService $subscriptions): View|RedirectResponse
    {
        if ($request->user()->isPro()) {
            return to_route('billing.index')->with('success', 'Your Pro subscription is already active.');
        }

        try {
            $remote = $gateway->create($request->user());
            $subscription = $subscriptions->createLocal($request->user(), Subscription::PROVIDER_RAZORPAY, $remote);

            return view('billing.razorpay-checkout', compact('subscription'));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'Razorpay sandbox could not start checkout.');
        }
    }

    public function confirmRazorpay(Request $request, RazorpaySubscriptionGateway $gateway, SubscriptionService $subscriptions): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_subscription_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'],
        ]);
        $subscription = $request->user()->subscriptions()
            ->where('provider', Subscription::PROVIDER_RAZORPAY)
            ->where('provider_subscription_id', $data['razorpay_subscription_id'])->firstOrFail();

        abort_unless($gateway->verifyCheckout(
            $data['razorpay_payment_id'],
            $data['razorpay_subscription_id'],
            $data['razorpay_signature'],
        ), 422, 'Razorpay checkout signature verification failed.');

        try {
            $subscriptions->sync($subscription, $gateway->fetch($subscription->provider_subscription_id));

            return to_route('billing.index')->with('success', $subscription->fresh()->grantsProAccess()
                ? 'PromptGrove Pro is now active.'
                : 'Payment authorization succeeded. Pro will activate when Razorpay confirms the subscription.');
        } catch (Throwable $exception) {
            return $this->failure($exception, 'Razorpay could not confirm the subscription.');
        }
    }

    public function paypal(Request $request, PayPalSubscriptionGateway $gateway, SubscriptionService $subscriptions): RedirectResponse
    {
        if ($request->user()->isPro()) {
            return to_route('billing.index')->with('success', 'Your Pro subscription is already active.');
        }

        try {
            $remote = $gateway->create($request->user());
            $subscription = $subscriptions->createLocal($request->user(), Subscription::PROVIDER_PAYPAL, $remote);
            $request->session()->put('billing.paypal_subscription_id', $subscription->provider_subscription_id);

            return redirect()->away($gateway->approvalUrl($remote));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'PayPal sandbox could not start checkout.');
        }
    }

    public function paypalReturn(Request $request, PayPalSubscriptionGateway $gateway, SubscriptionService $subscriptions): RedirectResponse
    {
        $pendingSubscriptionId = $request->session()->pull('billing.paypal_subscription_id');
        $subscriptionId = (string) ($request->query('subscription_id') ?: $pendingSubscriptionId);
        abort_if($subscriptionId === '', 422, 'PayPal did not return a subscription identifier.');
        $subscription = $request->user()->subscriptions()
            ->where('provider', Subscription::PROVIDER_PAYPAL)
            ->where('provider_subscription_id', $subscriptionId)->firstOrFail();

        try {
            $subscriptions->sync($subscription, $gateway->fetch($subscriptionId));

            return to_route('billing.index')->with('success', $subscription->fresh()->grantsProAccess()
                ? 'PromptGrove Pro is now active.'
                : 'PayPal approval was received. Pro will activate after PayPal confirms the subscription.');
        } catch (Throwable $exception) {
            return $this->failure($exception, 'PayPal could not confirm the subscription.');
        }
    }

    public function cancel(
        Request $request,
        Subscription $subscription,
        RazorpaySubscriptionGateway $razorpay,
        PayPalSubscriptionGateway $paypal,
        SubscriptionService $subscriptions,
    ): RedirectResponse {
        abort_unless($subscription->user_id === $request->user()->id, 404);
        abort_unless($subscription->grantsProAccess(), 422, 'This subscription is not active.');

        try {
            match ($subscription->provider) {
                Subscription::PROVIDER_RAZORPAY => $subscriptions->sync(
                    $subscription,
                    $razorpay->cancel($subscription->provider_subscription_id),
                ),
                Subscription::PROVIDER_PAYPAL => $this->cancelPayPal($subscription, $paypal),
                default => throw new BillingException('This payment provider is not supported.', 422),
            };

            return to_route('billing.index')->with('success', 'Your subscription was cancelled.');
        } catch (Throwable $exception) {
            return $this->failure($exception, 'The subscription could not be cancelled.');
        }
    }

    private function failure(Throwable $exception, string $fallback): RedirectResponse
    {
        Log::warning('Sandbox billing operation failed.', [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        return to_route('billing.index')->with('error', $exception instanceof BillingException ? $exception->getMessage() : $fallback);
    }

    private function cancelPayPal(Subscription $subscription, PayPalSubscriptionGateway $paypal): Subscription
    {
        $paypal->cancel($subscription->provider_subscription_id);
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now(), 'last_synced_at' => now()]);

        return $subscription;
    }
}
