<?php

use App\Enums\NameOfRoles;
use App\Enums\PaymentStatus;
use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\Company;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\Payments\PaymentLifeSycle;
use Modules\Central\Services\Payments\StripePaymentService;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createPaymentLifecycleUser(): User
{
    $user = User::create([
        'name' => 'Payment Lifecycle Owner',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::Owner->value);

    return $user;
}

function createPaymentLifecycleCompany(User $owner): Company
{
    return Company::withoutEvents(function () use ($owner) {

        $company = new Company([
            'name' => [
                'en' => 'Lifecycle Company ' . fake()->unique()->numberBetween(1000, 9999),
                'ar' => 'شركة دورة الدفع ' . fake()->unique()->numberBetween(1000, 9999),
            ],
            'subdomain' => 'lifecycle-' . fake()->unique()->numberBetween(100000, 999999),
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

function createPaymentLifecyclePrice(): SubscriptionPrice
{
    $plan = SubscriptionPlan::create([
        'name' => [
            'en' => 'Lifecycle Plan ' . fake()->unique()->numberBetween(1000, 9999),
            'ar' => 'خطة دورة الدفع ' . fake()->unique()->numberBetween(1000, 9999),
        ],
        'description' => [
            'en' => 'Payment lifecycle test plan',
            'ar' => 'خطة اختبار دورة الدفع',
        ],
        'is_active' => true,
    ]);

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => 99.99,
        'interval' => PriceInterval::MONTH->value,
        'stripe_price_id' => null,
        'is_active' => true,
        'has_trial' => false,
        'trial_days' => 0,
    ]);
}

function createPaymentLifecycleSubscription(
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

function createPaymentLifecyclePayment(
    Subscription $subscription,
    PaymentStatus $status = PaymentStatus::PENDING
): Payment {
    return Payment::create([
        'subscription_id' => $subscription->id,
        'amount' => $subscription->price->price,
        'currency' => 'usd',
        'status' => $status,
    ]);
}

/*
|--------------------------------------------------------------------------
| PAY
|--------------------------------------------------------------------------
*/

it('pays a pending payment and activates its subscription', function () {
    Event::fake([
        \App\Events\SubscriptionActivated::class,
    ]);

    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(PaymentLifeSycle::class);

    $result = $service->pay($payment);

    expect($result->status)
        ->toBe(PaymentStatus::PAID);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::ACTIVE);

    expect($result->paid_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::PAID->value,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    Event::assertDispatched(
        \App\Events\SubscriptionActivated::class
    );
});

it('does not process an already paid payment twice', function () {
    Event::fake([
        \App\Events\SubscriptionActivated::class,
    ]);

    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(PaymentLifeSycle::class);

    $first = $service->pay($payment);

    $second = $service->pay($payment);

    expect($first->status)
        ->toBe(PaymentStatus::PAID);

    expect($second->status)
        ->toBe(PaymentStatus::PAID);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::PAID->value,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    Event::assertDispatchedTimes(
        \App\Events\SubscriptionActivated::class,
        1
    );
});

/*
|--------------------------------------------------------------------------
| PAY - INVALID STATES
|--------------------------------------------------------------------------
*/

it('cannot pay a canceled payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::CANCELED
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->pay($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending payments can be paid.'
        );
});

it('cannot pay a failed payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::FAILED
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->pay($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending payments can be paid.'
        );
});

/*
|--------------------------------------------------------------------------
| CANCEL
|--------------------------------------------------------------------------
*/

it('cancels a pending payment and its subscription', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(PaymentLifeSycle::class);

    $result = $service->cancel($payment);

    expect($result->status)
        ->toBe(PaymentStatus::CANCELED);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::CANCELED);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::CANCELED->value,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::CANCELED->value,
    ]);
});

it('cannot cancel a paid payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PAID
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->cancel($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending payments can be canceled.'
        );
});

it('cannot cancel a failed payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::FAILED
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->cancel($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending payments can be canceled.'
        );
});

/*
|--------------------------------------------------------------------------
| RETRY
|--------------------------------------------------------------------------
*/

it('retries a failed payment and creates a checkout session', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::FAILED
    );

    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'url' => 'https://checkout.stripe.test/retry-session',
            'session_id' => 'cs_retry_test',
        ]);

    app()->instance(
        StripePaymentService::class,
        $stripe
    );

    $service = app(PaymentLifeSycle::class);

    $result = $service->retry($payment);

    expect($result->status)
        ->toBe(PaymentStatus::PENDING);

    expect($result->paid_at)
        ->toBeNull();

    expect($result->transaction_id)
        ->toBeNull();

    expect($result->checkout_url)
        ->toBe('https://checkout.stripe.test/retry-session');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::PENDING->value,
        'transaction_id' => null,
    ]);
});

it('cannot retry a pending payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->retry($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only failed payments can be retried.'
        );
});

it('cannot retry a failed payment when subscription is not pending', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::FAILED
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->retry($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending subscriptions can retry payment.'
        );
});

/*
|--------------------------------------------------------------------------
| FAIL
|--------------------------------------------------------------------------
*/

it('fails a pending payment and stores the failure reason', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $service = app(PaymentLifeSycle::class);

    $result = $service->fail(
        $payment,
        'card_declined'
    );

    expect($result->status)
        ->toBe(PaymentStatus::FAILED);

    expect($result->metadata)
        ->toMatchArray([
            'failure_reason' => 'card_declined',
        ]);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::FAILED->value,
    ]);
});

it('does not process an already failed payment twice', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::FAILED
    );

    $payment->update([
        'metadata' => [
            'failure_reason' => 'first_failure',
        ],
    ]);

    $service = app(PaymentLifeSycle::class);

    $result = $service->fail(
        $payment,
        'second_failure'
    );

    expect($result->status)
        ->toBe(PaymentStatus::FAILED);

    expect($result->metadata)
        ->toMatchArray([
            'failure_reason' => 'first_failure',
        ]);
});

it('cannot fail a paid payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PAID
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->fail(
        $payment,
        'card_declined'
    ))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only pending payments can fail.'
        );
});


it('refunds a paid payment and cancels its subscription', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PAID
    );

    $payment->update([
        'transaction_id' => 'pi_test_123',
    ]);

    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldReceive('refundPayment')
        ->once()
        ->with(Mockery::on(
            fn($paymentArg) => $paymentArg->id === $payment->id
        ))
        ->andReturn([
            'id' => 're_test_123',
            'status' => 'succeeded',
        ]);

    app()->instance(
        StripePaymentService::class,
        $stripe
    );

    $service = app(PaymentLifeSycle::class);

    $result = $service->refund($payment);

    expect($result->status)
        ->toBe(PaymentStatus::REFUNDED);

    expect($result->metadata)
        ->toMatchArray([
            'stripe_refund_id' => 're_test_123',
            'stripe_refund_status' => 'succeeded',
        ]);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::CANCELED);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::REFUNDED->value,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::CANCELED->value,
    ]);
});


it('cannot refund a pending payment', function () {
    $owner = createPaymentLifecycleUser();

    $company = createPaymentLifecycleCompany($owner);

    $price = createPaymentLifecyclePrice();

    $subscription = createPaymentLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $payment = createPaymentLifecyclePayment(
        $subscription,
        PaymentStatus::PENDING
    );

    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldReceive('refundPayment')
        ->never();

    app()->instance(
        StripePaymentService::class,
        $stripe
    );

    $service = app(PaymentLifeSycle::class);

    expect(fn() => $service->refund($payment))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Only paid payments can be refunded.'
        );
});
