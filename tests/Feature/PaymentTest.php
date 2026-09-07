<?php

use App\Enums\NameOfRoles;
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
use Modules\Central\Services\Payments\PaymentService;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createPaymentAdmin(): User
{
    $user = User::create([
        'name' => 'Payment Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createPaymentOwner(): User
{
    $user = User::create([
        'name' => 'Payment Owner',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::Owner->value);

    return $user;
}

function createPaymentUnauthorizedUser(): User
{
    return User::create([
        'name' => 'Unauthorized Payment User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

/*
|--------------------------------------------------------------------------
| Payment Plan
|--------------------------------------------------------------------------
*/

function createPaymentPlan(bool $active = true): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => [
            'en' => 'Payment Plan ' . fake()->unique()->numberBetween(1000, 9999),
            'ar' => 'خطة دفع ' . fake()->unique()->numberBetween(1000, 9999),
        ],
        'description' => [
            'en' => 'Payment test plan',
            'ar' => 'خطة اختبار الدفع',
        ],
        'is_active' => $active,
    ]);
}

/*
|--------------------------------------------------------------------------
| Payment Price
|--------------------------------------------------------------------------
*/

function createPaymentPrice(
    ?SubscriptionPlan $plan = null,
    bool $active = true,
    float $price = 99.99
): SubscriptionPrice {
    $plan ??= createPaymentPlan();

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => $price,
        'interval' => PriceInterval::MONTH->value,
        'stripe_price_id' => null,
        'is_active' => $active,
        'has_trial' => false,
        'trial_days' => 0,
    ]);
}

/*
|--------------------------------------------------------------------------
| Company
|--------------------------------------------------------------------------
*/

function createPaymentCompany(?User $owner = null): Company
{
    $owner ??= createPaymentOwner();

    return Company::withoutEvents(function () use ($owner) {

        $company = new Company([
            'name' => [
                'en' => 'Payment Company ' . fake()->unique()->numberBetween(1000, 9999),
                'ar' => 'شركة دفع ' . fake()->unique()->numberBetween(1000, 9999),
            ],
            'subdomain' => 'payment-company-' . fake()->unique()->numberBetween(100000, 999999),
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

/*
|--------------------------------------------------------------------------
| Subscription
|--------------------------------------------------------------------------
*/

function createPaymentSubscription(
    Company $company,
    SubscriptionPrice $price,
    SubscriptionStatus $status = SubscriptionStatus::PENDING
): Subscription {
    return Subscription::create([
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => $status->value,
        'start_date' => now(),
        'end_date' => now()->addMonth(),
        'trial_end_date' => null,
        'canceled_at' => null,
    ]);
}

/*
|--------------------------------------------------------------------------
| Payment
|--------------------------------------------------------------------------
*/

function createTestPayment(
    Subscription $subscription,
    ?float $amount = null,
    PaymentStatus $status = PaymentStatus::PENDING,
    array $extra = []
): Payment {
    return Payment::create(array_merge([
        'subscription_id' => $subscription->id,
        'amount' => $amount ?? $subscription->price->price,
        'currency' => 'usd',
        'status' => $status,
    ], $extra));
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

it('unauthenticated user cannot access payments', function () {
    $response = $this->getJson(
        '/api/v1/central/payments'
    );

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

it('can create a pending payment for a pending subscription', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $service = app(PaymentService::class);

    $payment = $service->store([
        'subscription_id' => $subscription->id,
        'amount' => $price->price,
        'currency' => 'usd',
        'status' => PaymentStatus::PENDING,
    ]);

    expect($payment)
        ->toBeInstanceOf(Payment::class)
        ->status->toBe(PaymentStatus::PENDING);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'subscription_id' => $subscription->id,
        'amount' => $price->price,
        'status' => PaymentStatus::PENDING->value,
    ]);
});

it('cannot create a second pending payment for the same subscription', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    createTestPayment(
        $subscription,
        status: PaymentStatus::PENDING
    );

    $service = app(PaymentService::class);

    expect(fn() => $service->store([
        'subscription_id' => $subscription->id,
        'amount' => $price->price,
        'currency' => 'usd',
        'status' => PaymentStatus::PENDING,
    ]))
        ->toThrow(
            \App\Exceptions\BusinessRuleException::class,
            'Subscription already has a pending payment.'
        );
});

it('cannot create a payment for a non pending subscription', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $service = app(PaymentService::class);

    expect(fn() => $service->store([
        'subscription_id' => $subscription->id,
        'amount' => $price->price,
        'currency' => 'usd',
        'status' => PaymentStatus::PENDING,
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});

/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
*/

it('can get a payment', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $service = app(PaymentService::class);

    $result = $service->get($payment);

    expect($result->id)
        ->toBe($payment->id);

    expect($result->relationLoaded('subscription'))
        ->toBeTrue();
});

it('admin can get all payments', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    createTestPayment($subscription);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/payments');

    $response->assertStatus(200);
});

it('owner can get his payments', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    createTestPayment($subscription);

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/central/payments');

    $response->assertStatus(200);
});

it('user without payment permission cannot get payments', function () {
    $user = createPaymentUnauthorizedUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/central/payments');

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

it('can update payment metadata', function () {
    $owner = createPaymentOwner();

    $company = createPaymentCompany($owner);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment(
        $subscription,
        extra: [
            'metadata' => [
                'source' => 'test',
            ],
        ]
    );

    $service = app(PaymentService::class);

    $updated = $service->update($payment, [
        'metadata' => [
            'source' => 'api',
            'test' => true,
        ],
    ]);

    expect($updated->status)
        ->toBe(PaymentStatus::PENDING);

    expect($updated->metadata)
        ->toBe([
            'source' => 'api',
            'test' => true,
        ]);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => PaymentStatus::PENDING->value,
    ]);
});

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

it('can delete a payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/payments/{$payment->id}"
        );

    $response->assertStatus(200);

    $this->assertSoftDeleted(
        'payments',
        ['id' => $payment->id]
    );
});

it('cannot delete a non existing payment', function () {
    $admin = createPaymentAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/payments/999999'
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| TRASHED
|--------------------------------------------------------------------------
*/

it('admin can get all trashed payments', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $payment->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/payments/trashed');

    $response->assertStatus(200);
});

it('admin can get a specific trashed payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $payment->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/payments/{$payment->id}/trashed"
        );

    $response->assertStatus(200);
});

it('cannot get a non trashed payment through trashed endpoint', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/payments/{$payment->id}/trashed"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| RESTORE
|--------------------------------------------------------------------------
*/

it('admin can restore a trashed payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $payment->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/payments/{$payment->id}/restore"
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'deleted_at' => null,
    ]);
});

it('cannot restore a non trashed payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/payments/{$payment->id}/restore"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| FORCE DELETE
|--------------------------------------------------------------------------
*/

it('admin can force delete a trashed payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $payment->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/payments/{$payment->id}/force-delete"
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('payments', [
        'id' => $payment->id,
    ]);
});

it('cannot force delete a non trashed payment', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment = createTestPayment($subscription);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/payments/{$payment->id}/force-delete"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| RESTORE ALL
|--------------------------------------------------------------------------
*/

it('admin can restore all trashed payments', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment1 = createTestPayment($subscription);

    $company2 = createPaymentCompany($admin);

    $price2 = createPaymentPrice();

    $subscription2 = createPaymentSubscription(
        $company2,
        $price2
    );

    $payment2 = createTestPayment($subscription2);

    $payment1->delete();
    $payment2->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/payments/restore-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'id' => $payment1->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('payments', [
        'id' => $payment2->id,
        'deleted_at' => null,
    ]);
});

it('cannot restore all payments when trash is empty', function () {
    $admin = createPaymentAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/payments/restore-all'
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| FORCE DELETE ALL
|--------------------------------------------------------------------------
*/

it('admin can force delete all trashed payments', function () {
    $admin = createPaymentAdmin();

    $company = createPaymentCompany($admin);

    $price = createPaymentPrice();

    $subscription = createPaymentSubscription(
        $company,
        $price
    );

    $payment1 = createTestPayment($subscription);

    $company2 = createPaymentCompany($admin);

    $price2 = createPaymentPrice();

    $subscription2 = createPaymentSubscription(
        $company2,
        $price2
    );

    $payment2 = createTestPayment($subscription2);

    $payment1->delete();
    $payment2->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/payments/force-delete-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('payments', [
        'id' => $payment1->id,
    ]);

    $this->assertDatabaseMissing('payments', [
        'id' => $payment2->id,
    ]);
});

it('cannot force delete all payments when trash is empty', function () {
    $admin = createPaymentAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/payments/force-delete-all'
        );

    $response->assertStatus(404);
});
