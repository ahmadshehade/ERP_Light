<?php

use App\Enums\NameOfRoles;
use App\Enums\PriceInterval;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createSubscriptionPriceAdmin(): User
{
    $user = User::create([
        'name' => 'Subscription Price Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createSubscriptionPriceUser(): User
{
    return User::create([
        'name' => 'Subscription Price User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

function createTestSubscriptionPlan(bool $isActive = true): SubscriptionPlan
{
    $unique = uniqid();

    return SubscriptionPlan::create([
        'name' => [
            'en' => "Test Plan {$unique}",
            'ar' => "خطة اختبار {$unique}",
        ],
        'description' => [
            'en' => 'Test subscription plan',
            'ar' => 'خطة اشتراك للاختبار',
        ],
        'is_active' => $isActive,
    ]);
}

function createTestSubscriptionPrice(
    ?SubscriptionPlan $plan = null,
    bool $isActive = true,
): SubscriptionPrice {
    $plan ??= createTestSubscriptionPlan();

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => 49.99,
        'interval' => PriceInterval::cases()[0]->value,
        'stripe_price_id' => null,
        'is_active' => $isActive,
        'has_trial' => false,
        'trial_days' => 0,
    ]);
}

function subscriptionPricePayload(
    SubscriptionPlan $plan,
    array $overrides = [],
): array {
    return array_merge([
        'plan_id' => $plan->id,
        'price' => 99.99,
        'interval' => PriceInterval::cases()[0]->value,
        'stripe_price_id' => null,
        'is_active' => true,
        'has_trial' => true,
        'trial_days' => 14,
    ], $overrides);
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

it('unauthenticated user cannot access subscription prices', function () {
    $response = $this->getJson(
        '/api/v1/central/subscriptionprices'
    );

    $response->assertStatus(401);
});


/*
|--------------------------------------------------------------------------
| Index
|--------------------------------------------------------------------------
*/

it('admin can get all subscription prices', function () {
    $admin = createSubscriptionPriceAdmin();

    createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/subscriptionprices');

    $response->assertStatus(200);
});

it('user without permission cannot get all subscription prices', function () {
    $user = createSubscriptionPriceUser();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/central/subscriptionprices');

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Show
|--------------------------------------------------------------------------
*/

it('admin can get a specific subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptionprices/{$price->id}"
        );

    $response->assertStatus(200);
});

it('user without permission cannot get a specific subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptionprices/{$price->id}"
        );

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Store
|--------------------------------------------------------------------------
*/

it('admin can create a subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan)
        );

    $response->assertStatus(201);

    $this->assertDatabaseHas('subscription_prices', [
        'plan_id' => $plan->id,
        'price' => 99.99,
        'is_active' => true,
        'has_trial' => true,
        'trial_days' => 14,
    ]);
});

it('user without permission cannot create a subscription price', function () {
    $user = createSubscriptionPriceUser();

    $plan = createTestSubscriptionPlan();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan)
        );

    $response->assertStatus(403);
});

it('cannot create subscription price without required fields', function () {
    $admin = createSubscriptionPriceAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            []
        );

    $response->assertStatus(422);
});

it('cannot create subscription price with invalid plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            [
                'plan_id' => 999999,
                'price' => 99.99,
                'interval' => PriceInterval::cases()[0]->value,
            ]
        );

    $response->assertStatus(422);
});

it('cannot create subscription price for inactive subscription plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan(false);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan)
        );

    /*
     * Validation passes because the plan exists.
     *
     * Service prepareData() then searches only for an
     * active SubscriptionPlan and therefore returns 404.
     */
    $response->assertStatus(404);
});

it('cannot create subscription price with negative price', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan, [
                'price' => -10,
            ])
        );

    $response->assertStatus(422);
});

it('cannot create subscription price with invalid interval', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan, [
                'interval' => 'invalid_interval',
            ])
        );

    $response->assertStatus(422);
});

it('cannot create subscription price with invalid active status', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices',
            subscriptionPricePayload($plan, [
                'is_active' => 'invalid',
            ])
        );

    $response->assertStatus(422);
});


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('admin can update a subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    $price = createTestSubscriptionPrice($plan);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'price' => 79.99,
                'interval' => PriceInterval::cases()[0]->value,
                'is_active' => true,
                'has_trial' => true,
                'trial_days' => 14,
            ]
        );

    $response->assertStatus(200);

    $price->refresh();

    expect((float) $price->price)->toBe(79.99)
        ->and($price->has_trial)->toBeTrue()
        ->and($price->trial_days)->toBe(14);
});

it('user without permission cannot update a subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'price' => 79.99,
            ]
        );

    $response->assertStatus(403);
});

it('admin can update subscription price plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan1 = createTestSubscriptionPlan();
    $plan2 = createTestSubscriptionPlan();

    $price = createTestSubscriptionPrice($plan1);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'plan_id' => $plan2->id,
            ]
        );

    $response->assertStatus(200);

    expect($price->refresh()->plan_id)
        ->toBe($plan2->id);
});

it('cannot update subscription price with invalid plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'plan_id' => 999999,
            ]
        );

    $response->assertStatus(422);
});

it('cannot update subscription price with negative price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'price' => -50,
            ]
        );

    $response->assertStatus(422);
});

it('cannot update subscription price with invalid interval', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$price->id}",
            [
                'interval' => 'invalid_interval',
            ]
        );

    $response->assertStatus(422);
});

it('cannot update active price to another active price of the same plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    /*
     * Existing active price.
     */
    createTestSubscriptionPrice($plan, true);

    /*
     * Target price is inactive.
     */
    $priceToUpdate = createTestSubscriptionPrice($plan, false);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(
            "/api/v1/central/subscriptionprices/{$priceToUpdate->id}",
            [
                'is_active' => true,
            ]
        );

    $response->assertStatus(409);
});


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

it('admin can delete a subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}"
        );

    $response->assertStatus(200);

    $this->assertSoftDeleted('subscription_prices', [
        'id' => $price->id,
    ]);
});

it('user without permission cannot delete a subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}"
        );

    $response->assertStatus(403);
});

it('cannot delete an already deleted subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $price->delete();

    /*
     * The normal DELETE route does not use withTrashed(),
     * so Laravel route model binding cannot resolve the
     * deleted model and returns 404.
     */
    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}"
        );

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Trashed - Index
|--------------------------------------------------------------------------
*/

it('admin can get all trashed subscription prices', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptionprices/trashed'
        );

    $response->assertStatus(200);
});

it('user without permission cannot get all trashed subscription prices', function () {
    $user = createSubscriptionPriceUser();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(
            '/api/v1/central/subscriptionprices/trashed'
        );

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Trashed - Show
|--------------------------------------------------------------------------
*/

it('admin can get a specific trashed subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptionprices/{$price->id}/trashed"
        );

    $response->assertStatus(200);
});

it('user without permission cannot get a specific trashed subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptionprices/{$price->id}/trashed"
        );

    $response->assertStatus(403);
});

it('cannot get a non trashed subscription price through trashed endpoint', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(
            "/api/v1/central/subscriptionprices/{$price->id}/trashed"
        );

    /*
     * The service explicitly throws 409 when the model
     * is not actually trashed.
     */
    $response->assertStatus(409);
});


/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

it('admin can restore a trashed subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptionprices/{$price->id}/restore"
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscription_prices', [
        'id' => $price->id,
        'deleted_at' => null,
    ]);
});

it('user without permission cannot restore a subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptionprices/{$price->id}/restore"
        );

    $response->assertStatus(403);
});

it('cannot restore a non trashed subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptionprices/{$price->id}/restore"
        );

    $response->assertStatus(404);
});

it('cannot restore a trashed active price when another active price exists for the same plan', function () {
    $admin = createSubscriptionPriceAdmin();

    $plan = createTestSubscriptionPlan();

    /*
     * First active price remains active.
     */
    createTestSubscriptionPrice($plan, true);

    /*
     * Second active price is then soft deleted.
     */
    $priceToRestore = createTestSubscriptionPrice($plan, true);

    $priceToRestore->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            "/api/v1/central/subscriptionprices/{$priceToRestore->id}/restore"
        );

    $response->assertStatus(409);

    $this->assertSoftDeleted('subscription_prices', [
        'id' => $priceToRestore->id,
    ]);
});


/*
|--------------------------------------------------------------------------
| Restore All
|--------------------------------------------------------------------------
*/

it('admin can restore all trashed subscription prices', function () {
    $admin = createSubscriptionPriceAdmin();

    $price1 = createTestSubscriptionPrice();
    $price2 = createTestSubscriptionPrice();

    $price1->delete();
    $price2->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices/restore-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscription_prices', [
        'id' => $price1->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('subscription_prices', [
        'id' => $price2->id,
        'deleted_at' => null,
    ]);
});

it('user without permission cannot restore all subscription prices', function () {
    $user = createSubscriptionPriceUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices/restore-all'
        );

    $response->assertStatus(403);
});

it('cannot restore all subscription prices when trash is empty', function () {
    $admin = createSubscriptionPriceAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson(
            '/api/v1/central/subscriptionprices/restore-all'
        );

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Force Delete
|--------------------------------------------------------------------------
*/

it('admin can force delete a trashed subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}/force-delete"
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('subscription_prices', [
        'id' => $price->id,
    ]);

    expect(
        SubscriptionPrice::withTrashed()->find($price->id)
    )->toBeNull();
});

it('user without permission cannot force delete a subscription price', function () {
    $user = createSubscriptionPriceUser();

    $price = createTestSubscriptionPrice();

    $price->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}/force-delete"
        );

    $response->assertStatus(403);
});

it('cannot force delete a non trashed subscription price', function () {
    $admin = createSubscriptionPriceAdmin();

    $price = createTestSubscriptionPrice();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/central/subscriptionprices/{$price->id}/force-delete"
        );

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Force Delete All
|--------------------------------------------------------------------------
*/

it('admin can force delete all trashed subscription prices', function () {
    $admin = createSubscriptionPriceAdmin();

    $price1 = createTestSubscriptionPrice();
    $price2 = createTestSubscriptionPrice();

    $price1->delete();
    $price2->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptionprices/force-delete-all'
        );

    $response->assertStatus(200);

    $this->assertDatabaseMissing('subscription_prices', [
        'id' => $price1->id,
    ]);

    $this->assertDatabaseMissing('subscription_prices', [
        'id' => $price2->id,
    ]);
});

it('user without permission cannot force delete all subscription prices', function () {
    $user = createSubscriptionPriceUser();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptionprices/force-delete-all'
        );

    $response->assertStatus(403);
});

it('cannot force delete all subscription prices when trash is empty', function () {
    $admin = createSubscriptionPriceAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson(
            '/api/v1/central/subscriptionprices/force-delete-all'
        );

    $response->assertStatus(404);
});
