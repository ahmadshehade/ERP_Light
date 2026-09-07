<?php

use App\Enums\PaymentStatus;
use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\Company;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\Payments\StripePaymentService;

use Stripe\StripeClient;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createStripeTestUser(): User
{
    return User::create([
        'name' => 'Stripe Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

function createStripeTestCompany(User $owner): Company
{
    return Company::withoutEvents(function () use ($owner) {
        $company = new Company([
            'name' => [
                'en' => 'Stripe Company ' . fake()->unique()->numberBetween(1000, 9999),
                'ar' => 'شركة Stripe ' . fake()->unique()->numberBetween(1000, 9999),
            ],
            'subdomain' => 'stripe-' . fake()->unique()->numberBetween(100000, 999999),
            'max_users' => 10,
            'is_active' => true,
        ]);

        $company->forceFill([
            'owner_id' => $owner->id,
        ]);

        $company->save();

        return $company;
    });
}

function createStripeTestPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => [
            'en' => 'Stripe Test Plan',
            'ar' => 'خطة Stripe للاختبار',
        ],
        'description' => [
            'en' => 'Stripe payment service test plan',
            'ar' => 'خطة اختبار خدمة دفع Stripe',
        ],
        'is_active' => true,
    ]);
}

function createStripeTestPrice(
    ?SubscriptionPlan $plan = null,
    float $amount = 99.99
): SubscriptionPrice {
    $plan ??= createStripeTestPlan();

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => $amount,
        'interval' => PriceInterval::MONTH->value,
        'stripe_price_id' => null,
        'is_active' => true,
        'has_trial' => false,
        'trial_days' => 0,
    ]);
}

function createStripeTestSubscription(
    Company $company,
    SubscriptionPrice $price,
    SubscriptionStatus $status = SubscriptionStatus::PENDING
): Subscription {
    return Subscription::withoutEvents(function () use (
        $company,
        $price,
        $status
    ) {
        return Subscription::create([
            'company_id' => $company->id,
            'price_id' => $price->id,
            'status' => $status->value,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'trial_end_date' => null,
            'canceled_at' => null,
        ]);
    });
}

function createStripeTestPayment(
    Subscription $subscription,
    PaymentStatus $status = PaymentStatus::PENDING,
    array $attributes = []
): Payment {
    return Payment::create(array_merge([
        'subscription_id' => $subscription->id,
        'amount' => $subscription->price->price,
        'currency' => 'usd',
        'status' => $status,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Stripe Mock Helpers
|--------------------------------------------------------------------------
*/

function injectStripeClientMock(
    StripePaymentService $service,
    StripeClient $stripe
): void {
    $reflection = new ReflectionClass($service);

    $property = $reflection->getProperty('stripe');
    $property->setValue($service, $stripe);
}

/*
|--------------------------------------------------------------------------
| CREATE CHECKOUT SESSION
|--------------------------------------------------------------------------
*/

it('creates a stripe checkout session and stores its id on payment', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $plan = createStripeTestPlan();

    $price = createStripeTestPrice(
        $plan,
        125.50
    );

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $session = new class {
        public string $id = 'cs_test_123';
        public string $url = 'https://checkout.stripe.test/cs_test_123';
    };

    $checkoutSessions = Mockery::mock();

    $checkoutSessions
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data) use ($payment, $price) {
            return
                $data['mode'] === 'payment'
                && $data['line_items'][0]['quantity'] === 1
                && $data['line_items'][0]['price_data']['currency'] === 'usd'
                && $data['line_items'][0]['price_data']['unit_amount'] === 12550
                && $data['metadata']['payment_id'] === (string) $payment->id
                && $data['metadata']['subscription_id'] === (string) $payment->subscription_id
                && $data['payment_intent_data']['metadata']['payment_id'] === (string) $payment->id;
        }))
        ->andReturn($session);

    $checkout = Mockery::mock();
    $checkout->sessions = $checkoutSessions;

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->checkout = $checkout;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $result = $service->createCheckoutSession($payment);

    expect($result)
        ->toBe([
            'url' => 'https://checkout.stripe.test/cs_test_123',
            'session_id' => 'cs_test_123',
        ]);

    $payment->refresh();

    expect($payment->checkout_session_id)
        ->toBe('cs_test_123');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'checkout_session_id' => 'cs_test_123',
    ]);
});


/*
|--------------------------------------------------------------------------
| EXPIRE CHECKOUT SESSION
|--------------------------------------------------------------------------
*/

it('does nothing when checkout session is already expired', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'checkout_session_id' => 'cs_expired',
        ]
    );

    $session = new class {
        public string $id = 'cs_expired';
        public string $status = 'expired';
    };

    $sessions = Mockery::mock();

    $sessions
        ->shouldReceive('retrieve')
        ->once()
        ->with('cs_expired')
        ->andReturn($session);

    $sessions
        ->shouldReceive('expire')
        ->never();

    $checkout = Mockery::mock();
    $checkout->sessions = $sessions;

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->checkout = $checkout;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $service->expireCheckoutSession($payment);

    expect(true)->toBeTrue();
});

it('cannot expire a completed checkout session', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'checkout_session_id' => 'cs_complete',
        ]
    );

    $session = new class {
        public string $id = 'cs_complete';
        public string $status = 'complete';
    };

    $sessions = Mockery::mock();

    $sessions
        ->shouldReceive('retrieve')
        ->once()
        ->with('cs_complete')
        ->andReturn($session);

    $sessions
        ->shouldReceive('expire')
        ->never();

    $checkout = Mockery::mock();
    $checkout->sessions = $sessions;

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->checkout = $checkout;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect(fn() => $service->expireCheckoutSession($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Cannot expire a completed Stripe Checkout Session.'
        );
});

it('expires an active checkout session', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'checkout_session_id' => 'cs_open',
        ]
    );

    $session = new class {
        public string $id = 'cs_open';
        public string $status = 'open';
    };

    $expiredSession = new class {
        public string $id = 'cs_open';
        public string $status = 'expired';
    };

    $sessions = Mockery::mock();

    $sessions
        ->shouldReceive('retrieve')
        ->once()
        ->with('cs_open')
        ->andReturn($session);

    $sessions
        ->shouldReceive('expire')
        ->once()
        ->with('cs_open')
        ->andReturn($expiredSession);

    $checkout = Mockery::mock();
    $checkout->sessions = $sessions;

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->checkout = $checkout;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $service->expireCheckoutSession($payment);

    expect(true)->toBeTrue();
});

it('cannot expire checkout session without a session id', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price
    );

    $payment = createStripeTestPayment(
        $subscription
    );

    $service = app(StripePaymentService::class);

    expect(fn() => $service->expireCheckoutSession($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Stripe Checkout Session ID not found.'
        );
});


/*
|--------------------------------------------------------------------------
| REFUND
|--------------------------------------------------------------------------
*/

it('creates a stripe refund for a payment intent', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID,
        [
            'transaction_id' => 'pi_test_123',
        ]
    );

    $refund = new class {
        public string $id = 're_test_123';
        public string $status = 'succeeded';

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'status' => $this->status,
            ];
        }
    };

    $refunds = Mockery::mock();

    $refunds
        ->shouldReceive('create')
        ->once()
        ->with([
            'payment_intent' => 'pi_test_123',
        ])
        ->andReturn($refund);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->refunds = $refunds;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $result = $service->refundPayment($payment);

    expect($result)
        ->toBe([
            'id' => 're_test_123',
            'status' => 'succeeded',
        ]);
});

it('cannot refund payment without transaction id', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID
    );

    $service = app(StripePaymentService::class);

    expect(fn() => $service->refundPayment($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Stripe transaction ID not found.'
        );
});


/*
|--------------------------------------------------------------------------
| CANCEL PAYMENT INTENT
|--------------------------------------------------------------------------
*/

it('cannot cancel a stripe payment intent that already succeeded', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID,
        [
            'transaction_id' => 'pi_succeeded',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_succeeded';
        public string $status = 'succeeded';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_succeeded')
        ->andReturn($paymentIntent);

    $paymentIntents
        ->shouldReceive('cancel')
        ->never();

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect(fn() => $service->cancelPayment($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Cannot cancel a payment that has already succeeded. Use refundPayment() instead.'
        );
});

it('returns already canceled stripe payment intent without canceling again', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'transaction_id' => 'pi_canceled',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_canceled';
        public string $status = 'canceled';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_canceled')
        ->andReturn($paymentIntent);

    $paymentIntents
        ->shouldReceive('cancel')
        ->never();

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $result = $service->cancelPayment($payment);

    expect($result)
        ->toMatchArray([
            'status' => 'canceled',
            'payment_intent_id' => 'pi_canceled',
        ]);
});

it('cancels a stripe payment intent', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'transaction_id' => 'pi_cancel',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_cancel';
        public string $status = 'requires_payment_method';
    };

    $canceledIntent = new class {
        public string $id = 'pi_cancel';
        public string $status = 'canceled';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_cancel')
        ->andReturn($paymentIntent);

    $paymentIntents
        ->shouldReceive('cancel')
        ->once()
        ->with('pi_cancel')
        ->andReturn($canceledIntent);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    $result = $service->cancelPayment($payment);

    expect($result)
        ->toMatchArray([
            'status' => 'canceled',
            'payment_intent_id' => 'pi_cancel',
        ]);
});

it('cannot cancel payment without transaction id', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription
    );

    $service = app(StripePaymentService::class);

    expect(fn() => $service->cancelPayment($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Stripe transaction ID not found.'
        );
});


/*
|--------------------------------------------------------------------------
| GET PAYMENT STATUS
|--------------------------------------------------------------------------
*/

it('gets stripe payment intent status', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID,
        [
            'transaction_id' => 'pi_status',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_status';
        public string $status = 'succeeded';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_status')
        ->andReturn($paymentIntent);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect($service->getPaymentStatus($payment))
        ->toBe('succeeded');
});

it('returns null when payment has no transaction id', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID
    );

    $service = app(StripePaymentService::class);

    expect($service->getPaymentStatus($payment))
        ->toBeNull();
});

it('returns null when stripe payment status cannot be retrieved', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID,
        [
            'transaction_id' => 'pi_error',
        ]
    );

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_error')
        ->andThrow(new RuntimeException('Stripe unavailable'));

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect($service->getPaymentStatus($payment))
        ->toBeNull();
});


/*
|--------------------------------------------------------------------------
| CAN CANCEL PAYMENT
|--------------------------------------------------------------------------
*/

it('returns false when payment has no transaction id', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(StripePaymentService::class);

    expect($service->canCancelPayment($payment))
        ->toBeFalse();
});

it('returns true when stripe payment intent is cancelable', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'transaction_id' => 'pi_cancelable',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_cancelable';
        public string $status = 'requires_payment_method';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_cancelable')
        ->andReturn($paymentIntent);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect($service->canCancelPayment($payment))
        ->toBeTrue();
});

it('returns false when stripe payment intent is not cancelable', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PAID,
        [
            'transaction_id' => 'pi_not_cancelable',
        ]
    );

    $paymentIntent = new class {
        public string $id = 'pi_not_cancelable';
        public string $status = 'succeeded';
    };

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_not_cancelable')
        ->andReturn($paymentIntent);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect($service->canCancelPayment($payment))
        ->toBeFalse();
});

it('returns false when stripe cannot be reached while checking cancellation', function () {
    $owner = createStripeTestUser();

    $company = createStripeTestCompany($owner);

    $price = createStripeTestPrice();

    $subscription = createStripeTestSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createStripeTestPayment(
        $subscription,
        PaymentStatus::PENDING,
        [
            'transaction_id' => 'pi_unavailable',
        ]
    );

    $paymentIntents = Mockery::mock();

    $paymentIntents
        ->shouldReceive('retrieve')
        ->once()
        ->with('pi_unavailable')
        ->andThrow(new RuntimeException('Stripe unavailable'));

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->paymentIntents = $paymentIntents;

    $service = app(StripePaymentService::class);

    injectStripeClientMock($service, $stripe);

    expect($service->canCancelPayment($payment))
        ->toBeFalse();
});
