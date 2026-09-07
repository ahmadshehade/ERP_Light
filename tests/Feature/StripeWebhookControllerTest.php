<?php

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Central\Models\Company;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\Payments\PaymentLifeSycle;
use Stripe\Webhook;

uses(DatabaseTransactions::class);

const STRIPE_TEST_WEBHOOK_SECRET = 'whsec_test_secret';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function stripeTestSignature(
    string $payload,
    string $secret
): string {
    /**
     * The timestamp of the webhook event in seconds since the Unix epoch.
     * @var int
     */
    $timestamp = time();

    $signedPayload = $timestamp . '.' . $payload;

    $signature = hash_hmac(
        'sha256',
        $signedPayload,
        $secret
    );

    return "t={$timestamp},v1={$signature}";
}

function postStripeWebhook(
    array|string $payload,
    ?string $signature = null
) {
    $payload = is_array($payload)
        ? json_encode($payload, JSON_UNESCAPED_SLASHES)
        : $payload;

    $signature ??= stripeTestSignature(
        $payload,
        STRIPE_TEST_WEBHOOK_SECRET
    );

    return test()->call(
        'POST',
        '/api/webhooks/stripe',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );
}

function stripeEventPayload(
    string $eventType,
    array $data,
    ?string $eventId = null
): array {
    return [
        'id' => $eventId ?? 'evt_' . uniqid(),
        'object' => 'event',
        'type' => $eventType,
        'data' => [
            'object' => $data,
        ],
    ];
}

/*
|--------------------------------------------------------------------------
| Database Helper
|--------------------------------------------------------------------------
*/

function createStripeWebhookPayment(
    string $paymentStatus = 'pending'
): Payment {

    $paymentStatus = PaymentStatus::from($paymentStatus);

    $owner = User::factory()->create();

    $company = new Company();

    $company->name = [
        'en' => 'Webhook Test Company',
    ];

    $company->subdomain = 'webhook-test-' . uniqid();

    $company->max_users = 10;

    $company->is_active = true;

    $company->database = 'webhook_test_' . uniqid();

    $company->owner_id = $owner->id;

    $company->save();

    $plan = SubscriptionPlan::create([
        'name' => [
            'en' => 'Webhook Test Plan',
        ],
        'description' => [
            'en' => 'Webhook test plan',
        ],
        'is_active' => true,
    ]);

    $price = SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => 100,
        'currency' => 'usd',
        'is_active' => true,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Payment Status != Subscription Status
    |--------------------------------------------------------------------------
    */

    $subscriptionStatus = $paymentStatus === PaymentStatus::PAID
        ? SubscriptionStatus::ACTIVE
        : SubscriptionStatus::PENDING;

    $subscription = Subscription::create([
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => $subscriptionStatus,
    ]);

    return Payment::create([
        'subscription_id' => $subscription->id,
        'amount' => 100,
        'currency' => 'usd',
        'status' => $paymentStatus,
        'metadata' => [],
    ]);
}

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

beforeEach(function () {

    config()->set(
        'services.stripe.webhook_secret',
        STRIPE_TEST_WEBHOOK_SECRET
    );
});

/*
|--------------------------------------------------------------------------
| Invalid Signature
|--------------------------------------------------------------------------
*/

it('rejects an invalid stripe webhook signature', function () {

    $payload = json_encode([
        'id' => 'evt_test',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
    ]);

    $response = postStripeWebhook(
        $payload,
        'invalid-signature'
    );

    $response
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid signature',
        ]);
});

/*
|--------------------------------------------------------------------------
| Invalid Payload
|--------------------------------------------------------------------------
*/

it('rejects an invalid stripe webhook payload', function () {

    $payload = '{invalid-json';

    $signature = stripeTestSignature(
        $payload,
        STRIPE_TEST_WEBHOOK_SECRET
    );

    $response = postStripeWebhook(
        $payload,
        $signature
    );

    $response
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid payload',
        ]);
});

/*
|--------------------------------------------------------------------------
| checkout.session.completed
|--------------------------------------------------------------------------
*/

it('stores stripe checkout information when checkout is completed and paid', function () {

    $payment = createStripeWebhookPayment();

    $payload = stripeEventPayload(
        'checkout.session.completed',
        [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_123',
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'payment_reference' => $payment->reference,
                'subscription_id' => (string) $payment->subscription_id,
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $payment->refresh();

    expect($payment->gateway)
        ->toBe('stripe')
        ->and($payment->transaction_id)
        ->toBe('pi_test_123')
        ->and($payment->metadata)
        ->toMatchArray([
            'stripe_session_id' => 'cs_test_123',
            'stripe_payment_intent_id' => 'pi_test_123',
        ])
        ->and($payment->status)
        ->toBe(PaymentStatus::PENDING);
});

it('does not process checkout session when stripe payment status is not paid', function () {

    $payment = createStripeWebhookPayment();

    $payload = stripeEventPayload(
        'checkout.session.completed',
        [
            'id' => 'cs_test_unpaid',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
            'payment_intent' => 'pi_test_unpaid',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $payment->refresh();

    expect($payment->gateway)
        ->toBeNull()
        ->and($payment->transaction_id)
        ->toBeNull()
        ->and($payment->status)
        ->toBe(PaymentStatus::PENDING);
});

it('ignores checkout session when payment does not exist', function () {

    $payload = stripeEventPayload(
        'checkout.session.completed',
        [
            'id' => 'cs_test_missing',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_missing',
            'metadata' => [
                'payment_id' => '999999999',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

/*
|--------------------------------------------------------------------------
| payment_intent.succeeded
|--------------------------------------------------------------------------
*/

it('pays a pending payment when payment intent succeeds', function () {

    $payment = createStripeWebhookPayment();

    Event::fake([
        \App\Events\SubscriptionActivated::class,
    ]);

    $paymentLifecycle = Mockery::mock(PaymentLifeSycle::class);

    $this->instance(
        PaymentLifeSycle::class,
        $paymentLifecycle
    );

    $paymentLifecycle
        ->shouldReceive('pay')
        ->once()
        ->withArgs(function (Payment $passedPayment) use ($payment) {

            return $passedPayment->id === $payment->id;
        })
        ->andReturnUsing(function (Payment $passedPayment) {

            $passedPayment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);

            return $passedPayment->fresh();
        });

    $payload = stripeEventPayload(
        'payment_intent.succeeded',
        [
            'id' => 'pi_success_123',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $payment->refresh();

    expect($payment->gateway)
        ->toBe('stripe')
        ->and($payment->transaction_id)
        ->toBe('pi_success_123')
        ->and($payment->metadata)
        ->toMatchArray([
            'stripe_payment_intent_id' => 'pi_success_123',
        ])
        ->and($payment->status)
        ->toBe(PaymentStatus::PAID);
});

it('does not pay an already paid payment again', function () {

    $payment = createStripeWebhookPayment('paid');

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('pay');

    $payload = stripeEventPayload(
        'payment_intent.succeeded',
        [
            'id' => 'pi_already_paid',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('ignores payment intent succeeded when payment does not exist', function () {

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('pay');

    $payload = stripeEventPayload(
        'payment_intent.succeeded',
        [
            'id' => 'pi_missing',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => '999999999',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('ignores payment intent succeeded when payment_id metadata is missing', function () {

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('pay');

    $payload = stripeEventPayload(
        'payment_intent.succeeded',
        [
            'id' => 'pi_without_payment_id',
            'object' => 'payment_intent',
            'metadata' => [],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

/*
|--------------------------------------------------------------------------
| payment_intent.payment_failed
|--------------------------------------------------------------------------
*/

it('fails a pending payment when payment intent fails', function () {

    $payment = createStripeWebhookPayment();

    $payment->refresh();

    expect($payment->status)
        ->toBe(PaymentStatus::PENDING);

    $paymentLifecycle = Mockery::mock(PaymentLifeSycle::class);

    $this->instance(
        PaymentLifeSycle::class,
        $paymentLifecycle
    );

    $paymentLifecycle
        ->shouldReceive('fail')
        ->once()
        ->withArgs(function (
            Payment $passedPayment,
            ?string $reason
        ) use ($payment) {

            return $passedPayment->id === $payment->id
                && $reason === 'Card was declined.';
        });

    $payload = stripeEventPayload(
        'payment_intent.payment_failed',
        [
            'id' => 'pi_failed_123',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'last_payment_error' => [
                'message' => 'Card was declined.',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('does not fail an already failed payment again', function () {

    $payment = createStripeWebhookPayment('failed');

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('fail');

    $payload = stripeEventPayload(
        'payment_intent.payment_failed',
        [
            'id' => 'pi_failed_again',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'last_payment_error' => [
                'message' => 'Card was declined.',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('does not fail a paid payment', function () {

    $payment = createStripeWebhookPayment('paid');

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('fail');

    $payload = stripeEventPayload(
        'payment_intent.payment_failed',
        [
            'id' => 'pi_failed_paid',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'last_payment_error' => [
                'message' => 'Card was declined.',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('ignores payment failed event when payment does not exist', function () {

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('fail');

    $payload = stripeEventPayload(
        'payment_intent.payment_failed',
        [
            'id' => 'pi_missing_failed',
            'object' => 'payment_intent',
            'metadata' => [
                'payment_id' => '999999999',
            ],
            'last_payment_error' => [
                'message' => 'Card was declined.',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

it('ignores payment failed event when payment_id metadata is missing', function () {

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('fail');

    $payload = stripeEventPayload(
        'payment_intent.payment_failed',
        [
            'id' => 'pi_failed_without_metadata',
            'object' => 'payment_intent',
            'metadata' => [],
            'last_payment_error' => [
                'message' => 'Card was declined.',
            ],
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

/*
|--------------------------------------------------------------------------
| Unknown Event
|--------------------------------------------------------------------------
*/

it('accepts unknown stripe event types without processing a payment', function () {

    $paymentLifecycle = mock(PaymentLifeSycle::class);

    $paymentLifecycle
        ->shouldNotReceive('pay')
        ->shouldNotReceive('fail');

    $payload = stripeEventPayload(
        'customer.created',
        [
            'id' => 'cus_test_123',
            'object' => 'customer',
        ]
    );

    $response = postStripeWebhook($payload);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});
