<?php

use App\Enums\NameOfRoles;
use App\Enums\PaymentStatus;
use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\Company;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\Payments\StripePaymentService;
use Modules\Central\Services\SubscriptionLifecycleService;
use Modules\Central\Services\Subscriptions\SubscriptionNotificationService;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createSubscriptionAdmin(): User
{
    $user = User::create([
        'name' => 'Subscription Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createSubscriptionOwner(): User
{
    $user = User::create([
        'name' => 'Subscription Owner',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::Owner->value);

    return $user;
}

function createSubscriptionUnauthorizedUser(): User
{
    return User::create([
        'name' => 'Unauthorized Subscription User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

/*
|--------------------------------------------------------------------------
| Subscription Plan
|--------------------------------------------------------------------------
*/

function createSubscriptionTestPlan(bool $active = true): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => [
            'en' => 'Plan ' . fake()->unique()->numberBetween(1000, 9999),
            'ar' => 'خطة ' . fake()->unique()->numberBetween(1000, 9999),
        ],
        'description' => [
            'en' => 'Test subscription plan',
            'ar' => 'خطة اشتراك للاختبار',
        ],
        'is_active' => $active,
    ]);
}

/*
|--------------------------------------------------------------------------
| Subscription Price
|--------------------------------------------------------------------------
*/

function createSubscriptionTestPrice(
    ?SubscriptionPlan $plan = null,
    bool $active = true,
    float $price = 99.99
): SubscriptionPrice {
    $plan ??= createSubscriptionTestPlan();

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => $price,
        'interval' => PriceInterval::cases()[0]->value,
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

function createSubscriptionCompany(?User $owner = null): Company
{
    $owner ??= createSubscriptionOwner();

    return Company::withoutEvents(function () use ($owner) {
        $company = new Company([
            'name' => [
                'en' => 'Company ' . fake()->unique()->numberBetween(1000, 9999),
                'ar' => 'شركة ' . fake()->unique()->numberBetween(1000, 9999),
            ],
            'subdomain' => 'company-' . fake()->unique()->numberBetween(100000, 999999),
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

function createSubscription(
    ?User $owner = null,
    ?Company $company = null,
    ?SubscriptionPrice $price = null,
    string|SubscriptionStatus $status = SubscriptionStatus::PENDING
): Subscription {
    $owner ??= createSubscriptionOwner();
    $company ??= createSubscriptionCompany($owner);
    $price ??= createSubscriptionTestPrice();

    return Subscription::create([
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => $status instanceof SubscriptionStatus
            ? $status->value
            : $status,
        'start_date' => now(),
        'end_date' => now()->addMonth(),
        'trial_end_date' => null,
        'canceled_at' => null,
    ]);
}

function subscriptionPayload(
    Company $company,
    SubscriptionPrice $price,
    array $overrides = []
): array {
    return array_merge([
        'company_id' => $company->id,
        'price_id' => $price->id,
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| Stripe Mock
|--------------------------------------------------------------------------
*/

function mockStripePaymentService(): void
{
    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldReceive('createCheckoutSession')
        ->andReturn([
            'url' => 'https://checkout.stripe.test/session_' . fake()->uuid(),
        ])
        ->byDefault();

    $stripe->shouldReceive('expireCheckoutSession')
        ->andReturnTrue()
        ->byDefault();

    app()->instance(StripePaymentService::class, $stripe);
}

/*
|--------------------------------------------------------------------------
| Notification Mock
|--------------------------------------------------------------------------
*/

function mockSubscriptionNotificationService(): void
{
    $notification = Mockery::mock(SubscriptionNotificationService::class);

    $notification->shouldReceive('SubscriptionCreateNotification')
        ->andReturnNull()
        ->byDefault();

    $notification->shouldReceive('ChangedNotification')
        ->andReturnNull()
        ->byDefault();

    $notification->shouldReceive('renewNotification')
        ->andReturnNull()
        ->byDefault();

    app()->instance(
        SubscriptionNotificationService::class,
        $notification
    );
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

it('unauthenticated user cannot access subscriptions', function () {
    $response = $this->getJson(
        '/api/v1/central/subscriptions'
    );

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

it('admin can get all subscriptions', function () {
    $admin = createSubscriptionAdmin();

    createSubscription();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/subscriptions');

    $response->assertStatus(200);
});

it('owner can get his own subscriptions', function () {
    $owner = createSubscriptionOwner();

    createSubscription($owner);

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/central/subscriptions');

    $response->assertStatus(200);
});

it('user without subscription permission cannot get subscriptions', function () {
    $user = createSubscriptionUnauthorizedUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/central/subscriptions');

    $response->assertStatus(403);
});

it('admin can filter subscriptions by company', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany();

    createSubscription(null, $company);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptions?company_id=' . $company->id
        );

    $response->assertStatus(200);
});

it('admin can filter subscriptions by price', function () {
    $admin = createSubscriptionAdmin();

    $price = createSubscriptionTestPrice();

    createSubscription(null, null, $price);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptions?price_id=' . $price->id
        );

    $response->assertStatus(200);
});

it('admin can filter subscriptions by status', function () {
    $admin = createSubscriptionAdmin();

    createSubscription(
        null,
        null,
        null,
        SubscriptionStatus::PENDING
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptions?status=' .
                SubscriptionStatus::PENDING->value
        );

    $response->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

it('admin can get a specific subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(200);
});

it('subscription owner can get his own subscription', function () {
    $owner = createSubscriptionOwner();

    $subscription = createSubscription($owner);

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(200);
});

it('user cannot view another users subscription', function () {
    $owner = createSubscriptionOwner();

    $anotherUser = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription($owner);

    $response = $this
        ->actingAs($anotherUser, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(403);
});

it('user without permission cannot view a subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(403);
});

it('cannot get a non existing subscription', function () {
    $admin = createSubscriptionAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptions/999999'
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

it('admin can create a subscription', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            subscriptionPayload($company, $price)
        );

    $response->assertStatus(201);

    $this->assertDatabaseHas('subscriptions', [
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => SubscriptionStatus::PENDING->value,
    ]);

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $response->json('data.id'),
        'status' => PaymentStatus::PENDING->value,
    ]);
});

it('owner can create a subscription for his company', function () {
    $owner = createSubscriptionOwner();

    $company = createSubscriptionCompany($owner);

    $price = createSubscriptionTestPrice();

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            subscriptionPayload($company, $price)
        );

    $response->assertStatus(201);

    $this->assertDatabaseHas('subscriptions', [
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => SubscriptionStatus::PENDING->value,
    ]);
});

it('user without company cannot create a subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $price = createSubscriptionTestPrice();

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            [
                'company_id' => fake()->numberBetween(100000, 999999),
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(403);
});

it('cannot create subscription without required fields', function () {
    $admin = createSubscriptionAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            []
        );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'company_id',
        'price_id',
    ]);
});

it('cannot create subscription with invalid company', function () {
    $admin = createSubscriptionAdmin();

    $price = createSubscriptionTestPrice();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            [
                'company_id' => 999999,
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(422);
});

it('cannot create subscription with invalid price', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            [
                'company_id' => $company->id,
                'price_id' => 999999,
            ]
        );

    $response->assertStatus(422);
});

it('cannot create subscription using inactive price', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice(null, false);

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            subscriptionPayload($company, $price)
        );

    expect($response->status())->toBeIn([404, 422]);
});

it('cannot create another pending or active subscription for same company', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions',
            subscriptionPayload($company, $price)
        );

    $response->assertStatus(409);
});

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

it('admin can update a pending subscription', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'company_id' => $company->id,
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(200);
});

it('owner can update his pending subscription', function () {
    $owner = createSubscriptionOwner();

    $company = createSubscriptionCompany($owner);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $owner,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'company_id' => $company->id,
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(200);
});

it('user without permission cannot update subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'price_id' => $subscription->price_id,
            ]
        );

    $response->assertStatus(403);
});

it('cannot update active subscription company', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'company_id' => $company->id,
            ]
        );

    $response->assertStatus(409);
});

it('cannot update active subscription price', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $newPrice = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'price_id' => $newPrice->id,
            ]
        );

    $response->assertStatus(409);
});

it('cannot update canceled subscription company or price', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $newPrice = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::CANCELED
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'price_id' => $newPrice->id,
            ]
        );

    $response->assertStatus(409);
});

it('cannot update subscription with invalid price', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'price_id' => 999999,
            ]
        );

    $response->assertStatus(422);
});

it('replaces pending payment when pending subscription price changes', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $oldPrice = createSubscriptionTestPrice(
        null,
        true,
        99.99
    );

    $newPrice = createSubscriptionTestPrice(
        null,
        true,
        199.99
    );

    $subscription = createSubscription(
        $admin,
        $company,
        $oldPrice,
        SubscriptionStatus::PENDING
    );

    $oldPayment = \Modules\Central\Models\Payment::create([
        'subscription_id' => $subscription->id,
        'amount' => $oldPrice->price,
        'currency' => 'usd',
        'status' => PaymentStatus::PENDING,
        'checkout_session_id' => 'cs_test_old',
    ]);

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptions/{$subscription->id}",
            [
                'company_id' => $company->id,
                'price_id' => $newPrice->id,
            ]
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'price_id' => $newPrice->id,
        'status' => SubscriptionStatus::PENDING->value,
    ]);

    $this->assertDatabaseHas('payments', [
        'id' => $oldPayment->id,
        'status' => PaymentStatus::CANCELED->value,
    ]);

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $subscription->id,
        'amount' => 199.99,
        'status' => PaymentStatus::PENDING->value,
    ]);

    expect(
        \Modules\Central\Models\Payment::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', PaymentStatus::PENDING->value)
            ->count()
    )->toBe(1);
});

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

it('admin can delete a pending subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription(
        $admin,
        null,
        null,
        SubscriptionStatus::PENDING
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(200);

    $this->assertSoftDeleted(
        'subscriptions',
        ['id' => $subscription->id]
    );
});

it('owner can delete his pending subscription', function () {
    $owner = createSubscriptionOwner();

    $subscription = createSubscription(
        $owner,
        null,
        null,
        SubscriptionStatus::PENDING
    );

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(200);

    $this->assertSoftDeleted(
        'subscriptions',
        ['id' => $subscription->id]
    );
});

it('cannot delete active subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription(
        $admin,
        null,
        null,
        SubscriptionStatus::ACTIVE
    );

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(409);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'deleted_at' => null,
    ]);
});

it('user without permission cannot delete subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(403);
});

it('cannot delete already deleted subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription(
        $admin,
        null,
        null,
        SubscriptionStatus::PENDING
    );

    $subscription->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| TRASHED
|--------------------------------------------------------------------------
*/

it('admin can get all trashed subscriptions', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/subscriptions/trashed');

    $response->assertStatus(200);
});

it('user without permission cannot get trashed subscriptions', function () {
    $user = createSubscriptionUnauthorizedUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/central/subscriptions/trashed');

    $response->assertStatus(403);
});

it('admin can get a specific trashed subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}/trashed"
        );

    $response->assertStatus(200);
});

it('user without permission cannot get a specific trashed subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}/trashed"
        );

    $response->assertStatus(403);
});

it('cannot get non trashed subscription through trashed endpoint', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptions/{$subscription->id}/trashed"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| RESTORE
|--------------------------------------------------------------------------
*/

it('admin can restore a trashed subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/restore"
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'deleted_at' => null,
    ]);
});

it('user without permission cannot restore subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/restore"
        );

    $response->assertStatus(403);
});

it('cannot restore non trashed subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/restore"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| RESTORE ALL
|--------------------------------------------------------------------------
*/

it('admin can restore all trashed subscriptions', function () {
    $admin = createSubscriptionAdmin();

    $subscription1 = createSubscription();
    $subscription2 = createSubscription();

    $subscription1->delete();
    $subscription2->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions/restore-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription1->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription2->id,
        'deleted_at' => null,
    ]);
});

it('user without permission cannot restore all subscriptions', function () {
    $user = createSubscriptionUnauthorizedUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions/restore-all'
        );

    $response->assertStatus(403);
});

it('cannot restore all subscriptions when trash is empty', function () {
    $admin = createSubscriptionAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions/restore-all'
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| FORCE DELETE
|--------------------------------------------------------------------------
*/

it('admin can force delete a trashed subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}/force-delete"
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('subscriptions', [
        'id' => $subscription->id,
    ]);
});

it('user without permission cannot force delete subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $subscription->delete();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}/force-delete"
        );

    $response->assertStatus(403);
});

it('cannot force delete non trashed subscription', function () {
    $admin = createSubscriptionAdmin();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptions/{$subscription->id}/force-delete"
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| FORCE DELETE ALL
|--------------------------------------------------------------------------
*/

it('admin can force delete all trashed subscriptions', function () {
    $admin = createSubscriptionAdmin();

    $subscription1 = createSubscription();
    $subscription2 = createSubscription();

    $subscription1->delete();
    $subscription2->delete();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptions/force-delete-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('subscriptions', [
        'id' => $subscription1->id,
    ]);

    $this->assertDatabaseMissing('subscriptions', [
        'id' => $subscription2->id,
    ]);
});

it('user without permission cannot force delete all subscriptions', function () {
    $user = createSubscriptionUnauthorizedUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptions/force-delete-all'
        );

    $response->assertStatus(403);
});

it('cannot force delete all when trash is empty', function () {
    $admin = createSubscriptionAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptions/force-delete-all'
        );

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| RENEW
|--------------------------------------------------------------------------
*/

it('admin can renew a subscription', function () {
    $admin = createSubscriptionAdmin();

    $company = createSubscriptionCompany($admin);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $newSubscription = createSubscription(
        $admin,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $lifecycle = Mockery::mock(SubscriptionLifecycleService::class);

    $lifecycle->shouldReceive('renew')
        ->once()
        ->andReturn($newSubscription);

    app()->instance(
        SubscriptionLifecycleService::class,
        $lifecycle
    );

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/renew",
            [
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(200);
});

it('user without renew permission cannot renew subscription', function () {
    $user = createSubscriptionUnauthorizedUser();

    $subscription = createSubscription();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/renew",
            []
        );

    $response->assertStatus(403);
});

it('owner can renew his own subscription', function () {
    $owner = createSubscriptionOwner();

    $company = createSubscriptionCompany($owner);

    $price = createSubscriptionTestPrice();

    $subscription = createSubscription(
        $owner,
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $newSubscription = createSubscription(
        $owner,
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $lifecycle = Mockery::mock(SubscriptionLifecycleService::class);

    $lifecycle->shouldReceive('renew')
        ->once()
        ->andReturn($newSubscription);

    app()->instance(
        SubscriptionLifecycleService::class,
        $lifecycle
    );

    mockStripePaymentService();
    mockSubscriptionNotificationService();

    $response = $this
        ->actingAs($owner, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptions/{$subscription->id}/renew",
            [
                'price_id' => $price->id,
            ]
        );

    $response->assertStatus(200);
});

it('cannot renew a non existing subscription', function () {
    $admin = createSubscriptionAdmin();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptions/999999/renew',
            []
        );

    $response->assertStatus(404);
});
