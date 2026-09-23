<?php

namespace Tests\Feature;

use App\Models\PaymentWebhookEvent;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PromptAnalysisDispatcher;
use App\Services\PromptVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.razorpay.base_url' => 'https://razorpay.test/v1',
            'billing.razorpay.key_id' => 'rzp_test_key',
            'billing.razorpay.key_secret' => 'razor-secret',
            'billing.razorpay.plan_id' => 'plan_1234567890abcd',
            'billing.razorpay.webhook_secret' => 'razor-webhook-secret',
            'billing.paypal.base_url' => 'https://paypal.test',
            'billing.paypal.client_id' => 'paypal-client',
            'billing.paypal.client_secret' => 'paypal-secret',
            'billing.paypal.plan_id' => 'P-PRO-MONTHLY',
            'billing.paypal.webhook_id' => 'WH-PROMPTGROVE',
            'prompt-analysis.per_hour' => 5,
            'prompt-analysis.pro_per_hour' => 25,
        ]);
    }

    public function test_free_users_see_pricing_and_are_redirected_from_pro_features(): void
    {
        $user = User::factory()->create();
        $prompt = Prompt::factory()->for($user)->create();
        $version = PromptVersion::factory()->for($prompt)->create(['created_by' => $user->id]);

        $this->actingAs($user)->get(route('billing.index'))->assertOk()->assertSee('$9')->assertSee('PayPal sandbox');
        $this->actingAs($user)->get(route('collections.index'))->assertRedirect(route('billing.index'));
        $this->actingAs($user)->get(route('prompts.history.show', [$prompt, $version]))->assertRedirect(route('billing.index'));
        $this->assertFalse($user->isPro());
    }

    public function test_only_active_unexpired_subscriptions_grant_pro_access(): void
    {
        $active = User::factory()->create();
        $expired = User::factory()->create();
        $cancelled = User::factory()->create();
        Subscription::factory()->for($active)->active()->create();
        Subscription::factory()->for($expired)->active()->create(['current_period_end' => now()->subMinute()]);
        Subscription::factory()->for($cancelled)->create(['status' => 'cancelled']);

        $this->assertTrue($active->isPro());
        $this->assertFalse($expired->isPro());
        $this->assertFalse($cancelled->isPro());
        $this->actingAs($active)->get(route('collections.index'))->assertOk();
    }

    public function test_pro_users_receive_the_higher_ai_analysis_quota(): void
    {
        $free = User::factory()->create();
        $pro = User::factory()->create();
        Subscription::factory()->for($pro)->active()->create();
        $dispatcher = app(PromptAnalysisDispatcher::class);

        $this->assertSame(5, $dispatcher->remaining($free));
        $this->assertSame(25, $dispatcher->remaining($pro));
    }

    public function test_version_snapshots_are_created_only_for_pro_users(): void
    {
        $free = User::factory()->create();
        $pro = User::factory()->create();
        Subscription::factory()->for($pro)->active()->create();
        $freePrompt = Prompt::factory()->for($free)->create();
        $proPrompt = Prompt::factory()->for($pro)->create();
        $versions = app(PromptVersionService::class);

        $this->assertNull($versions->record($freePrompt, $free));
        $this->assertNotNull($versions->record($proPrompt, $pro));
        $this->assertSame(0, $freePrompt->versions()->count());
        $this->assertSame(1, $proPrompt->versions()->count());
    }

    public function test_razorpay_checkout_creates_a_local_pending_subscription(): void
    {
        Http::fake(['razorpay.test/*' => Http::response($this->razorpaySubscription('created'), 200)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('billing.razorpay'))
            ->assertOk()->assertViewIs('billing.razorpay-checkout')->assertSee('sub_promptgrove');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'provider' => Subscription::PROVIDER_RAZORPAY,
            'provider_subscription_id' => 'sub_promptgrove',
            'status' => 'created',
        ]);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://razorpay.test/v1/subscriptions'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_key:razor-secret'))
            && $request['plan_id'] === 'plan_1234567890abcd');
    }

    public function test_razorpay_confirmation_requires_a_valid_signature_and_remote_active_status(): void
    {
        Http::fake(['razorpay.test/*' => Http::response($this->razorpaySubscription('active'), 200)]);
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create([
            'provider' => Subscription::PROVIDER_RAZORPAY,
            'provider_subscription_id' => 'sub_promptgrove',
            'provider_plan_id' => 'plan_1234567890abcd',
        ]);
        $paymentId = 'pay_sandbox';
        $signature = hash_hmac('sha256', $paymentId.'|'.$subscription->provider_subscription_id, 'razor-secret');

        $this->actingAs($user)->post(route('billing.razorpay.confirm'), [
            'razorpay_payment_id' => $paymentId,
            'razorpay_subscription_id' => $subscription->provider_subscription_id,
            'razorpay_signature' => $signature,
        ])->assertRedirect(route('billing.index'));

        $this->assertTrue($user->isPro());
        $this->assertSame('active', $subscription->fresh()->status);

        $this->actingAs($user)->post(route('billing.razorpay.confirm'), [
            'razorpay_payment_id' => $paymentId,
            'razorpay_subscription_id' => $subscription->provider_subscription_id,
            'razorpay_signature' => str_repeat('0', 64),
        ])->assertStatus(422);
    }

    public function test_checkout_callbacks_cannot_claim_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        Subscription::factory()->for($owner)->create([
            'provider' => Subscription::PROVIDER_RAZORPAY,
            'provider_subscription_id' => 'sub_promptgrove',
            'provider_plan_id' => 'plan_1234567890abcd',
        ]);
        $paymentId = 'pay_sandbox';
        $signature = hash_hmac('sha256', $paymentId.'|sub_promptgrove', 'razor-secret');

        $this->actingAs($attacker)->post(route('billing.razorpay.confirm'), [
            'razorpay_payment_id' => $paymentId,
            'razorpay_subscription_id' => 'sub_promptgrove',
            'razorpay_signature' => $signature,
        ])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_paypal_checkout_uses_sandbox_oauth_and_redirects_to_approval(): void
    {
        Http::fake([
            'paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-token'], 200),
            'paypal.test/v1/billing/subscriptions' => Http::response($this->paypalSubscription('APPROVAL_PENDING'), 201),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('billing.paypal'))
            ->assertRedirect('https://www.sandbox.paypal.com/approve/I-PROMPTGROVE');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'provider' => Subscription::PROVIDER_PAYPAL,
            'provider_subscription_id' => 'I-PROMPTGROVE',
        ]);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://paypal.test/v1/billing/subscriptions'
            && $request->hasHeader('Authorization', 'Bearer sandbox-token')
            && $request['plan_id'] === 'P-PRO-MONTHLY');
    }

    public function test_paypal_return_rechecks_subscription_with_paypal_before_unlocking_pro(): void
    {
        Http::fake([
            'paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-token'], 200),
            'paypal.test/v1/billing/subscriptions/*' => Http::response($this->paypalSubscription('ACTIVE'), 200),
        ]);
        $user = User::factory()->create();
        Subscription::factory()->for($user)->create([
            'provider_subscription_id' => 'I-PROMPTGROVE',
            'provider_plan_id' => 'P-PRO-MONTHLY',
        ]);

        $this->actingAs($user)->get(route('billing.paypal.return', ['subscription_id' => 'I-PROMPTGROVE']))
            ->assertRedirect(route('billing.index'));

        $this->assertTrue($user->isPro());
    }

    public function test_signed_razorpay_webhooks_activate_once_and_reject_bad_signatures(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create([
            'provider' => Subscription::PROVIDER_RAZORPAY,
            'provider_subscription_id' => 'sub_promptgrove',
            'provider_plan_id' => 'plan_1234567890abcd',
        ]);
        $payload = [
            'id' => 'evt_razor_1',
            'event' => 'subscription.activated',
            'payload' => ['subscription' => ['entity' => $this->razorpaySubscription('active')]],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'razor-webhook-secret');

        $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $raw)->assertOk();
        $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $raw)->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'bad',
        ], $raw)->assertUnauthorized();
    }

    public function test_verified_paypal_webhooks_activate_once(): void
    {
        Http::fake([
            'paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-token'], 200),
            'paypal.test/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
        ]);
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create([
            'provider_subscription_id' => 'I-PROMPTGROVE',
            'provider_plan_id' => 'P-PRO-MONTHLY',
        ]);
        $payload = [
            'id' => 'WH-EVENT-1',
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource' => $this->paypalSubscription('ACTIVE'),
        ];
        $headers = [
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-CERT-URL' => 'https://paypal.test/cert.pem',
            'PAYPAL-TRANSMISSION-ID' => 'transmission-1',
            'PAYPAL-TRANSMISSION-SIG' => 'sandbox-signature',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];

        $this->withHeaders($headers)->postJson(route('webhooks.paypal'), $payload)->assertOk();
        $this->withHeaders($headers)->postJson(route('webhooks.paypal'), $payload)->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('processed', PaymentWebhookEvent::firstOrFail()->status);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_active_subscription_can_be_cancelled_at_its_provider(): void
    {
        Http::fake(['razorpay.test/*' => Http::response($this->razorpaySubscription('cancelled'), 200)]);
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->active()->create([
            'provider' => Subscription::PROVIDER_RAZORPAY,
            'provider_subscription_id' => 'sub_promptgrove',
            'provider_plan_id' => 'plan_1234567890abcd',
        ]);

        $this->actingAs($user)->delete(route('billing.cancel', $subscription))->assertRedirect(route('billing.index'));

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->cancelled_at);
        $this->assertFalse($user->isPro());
    }

    public function test_live_razorpay_keys_are_refused_by_the_sandbox_only_integration(): void
    {
        config(['billing.razorpay.key_id' => 'rzp_live_never_allowed']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('billing.razorpay'))
            ->assertRedirect(route('billing.index'))->assertSessionHas('error');
        Http::assertNothingSent();
    }

    private function razorpaySubscription(string $status): array
    {
        return [
            'id' => 'sub_promptgrove',
            'plan_id' => 'plan_1234567890abcd',
            'status' => $status,
            'current_start' => now()->subDay()->timestamp,
            'current_end' => now()->addMonth()->timestamp,
        ];
    }

    private function paypalSubscription(string $status): array
    {
        return [
            'id' => 'I-PROMPTGROVE',
            'plan_id' => 'P-PRO-MONTHLY',
            'status' => $status,
            'start_time' => now()->subDay()->toIso8601String(),
            'billing_info' => ['next_billing_time' => now()->addMonth()->toIso8601String()],
            'links' => [[
                'href' => 'https://www.sandbox.paypal.com/approve/I-PROMPTGROVE',
                'rel' => 'approve',
                'method' => 'GET',
            ]],
        ];
    }
}
