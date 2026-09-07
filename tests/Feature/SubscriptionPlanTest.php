<?php

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\SubscriptionPlan;

uses(DatabaseTransactions::class);


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createSubscriptionPlanAdmin(): User
{
    $user = User::create([
        'name' => 'Subscription Plan Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createSubscriptionPlanUser(): User
{
    return User::create([
        'name' => 'Subscription Plan User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

function createSubscriptionPlan(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => [
            'en' => fake()->unique()->words(2, true),
            'ar' => 'خطة ' . fake()->unique()->word(),
        ],
        'description' => [
            'en' => 'Test subscription plan description',
            'ar' => 'وصف خطة الاشتراك التجريبية',
        ],
        'is_active' => true,
    ], $attributes));
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

test('unauthenticated user cannot access subscription plans', function () {

    $response = $this->getJson('/api/v1/central/subscriptionplans');

    $response->assertStatus(401);
});


/*
|--------------------------------------------------------------------------
| Index
|--------------------------------------------------------------------------
*/

test('admin can get all subscription plans', function () {

    $admin = createSubscriptionPlanAdmin();

    createSubscriptionPlan();
    createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/subscriptionplans');

    $response->assertStatus(200);
});


/*
|--------------------------------------------------------------------------
| Show
|--------------------------------------------------------------------------
*/

test('admin can get a specific subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/subscriptionplans/{$plan->id}");

    $response->assertStatus(200);
});

test('user without permission cannot get a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/central/subscriptionplans/{$plan->id}");

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Store
|--------------------------------------------------------------------------
*/

test('admin can create a subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $data = [
        'name_en' => 'Professional Plan',
        'name_ar' => 'الخطة الاحترافية',
        'description_en' => 'Professional subscription plan',
        'description_ar' => 'خطة الاشتراك الاحترافية',
        'is_active' => true,
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', $data);

    $response->assertStatus(201);

    $this->assertDatabaseHas('subscription_plans', [
        'is_active' => true,
    ]);

    $plan = SubscriptionPlan::where('name->en', 'Professional Plan')->first();

    expect($plan)->not->toBeNull();
});

test('user without permission cannot create a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', [
            'name_en' => 'Unauthorized Plan',
            'name_ar' => 'خطة غير مصرح بها',
            'description_en' => 'Description',
            'description_ar' => 'الوصف',
            'is_active' => true,
        ]);

    $response->assertStatus(403);
});

test('cannot create subscription plan without required fields', function () {

    $admin = createSubscriptionPlanAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name.en',
            'name.ar',
            'is_active',
        ]);
});

test('cannot create subscription plan with existing english name', function () {

    $admin = createSubscriptionPlanAdmin();

    createSubscriptionPlan([
        'name' => [
            'en' => 'Basic Plan',
            'ar' => 'الخطة الأساسية',
        ],
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', [
            'name_en' => 'Basic Plan',
            'name_ar' => 'خطة جديدة',
            'is_active' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name.en']);
});

test('cannot create subscription plan with existing arabic name', function () {

    $admin = createSubscriptionPlanAdmin();

    createSubscriptionPlan([
        'name' => [
            'en' => 'Basic Plan',
            'ar' => 'الخطة الأساسية',
        ],
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', [
            'name_en' => 'Another Plan',
            'name_ar' => 'الخطة الأساسية',
            'is_active' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name.ar']);
});

test('cannot create subscription plan with invalid active status', function () {

    $admin = createSubscriptionPlanAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans', [
            'name_en' => 'Invalid Status Plan',
            'name_ar' => 'خطة حالة غير صحيحة',
            'is_active' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['is_active']);
});


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

test('admin can update a subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/central/subscriptionplans/{$plan->id}", [
            'name_en' => 'Updated Plan',
            'name_ar' => 'الخطة المحدثة',
            'description_en' => 'Updated description',
            'description_ar' => 'الوصف المحدث',
            'is_active' => true,
        ]);

    $response->assertStatus(200);

    $plan->refresh();

    expect($plan->getTranslation('name', 'en'))->toBe('Updated Plan')
        ->and($plan->getTranslation('name', 'ar'))->toBe('الخطة المحدثة');
});

test('user without permission cannot update a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/central/subscriptionplans/{$plan->id}", [
            'name_en' => 'Unauthorized Update',
            'name_ar' => 'تعديل غير مصرح',
            'is_active' => true,
        ]);

    $response->assertStatus(403);
});

test('admin cannot update subscription plan with existing english name', function () {

    $admin = createSubscriptionPlanAdmin();

    createSubscriptionPlan([
        'name' => [
            'en' => 'Existing Plan',
            'ar' => 'خطة موجودة',
        ],
    ]);

    $plan = createSubscriptionPlan([
        'name' => [
            'en' => 'Current Plan',
            'ar' => 'الخطة الحالية',
        ],
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/central/subscriptionplans/{$plan->id}", [
            'name_en' => 'Existing Plan',
            'name_ar' => 'الخطة الجديدة',
            'is_active' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en']);
});

test('admin cannot update subscription plan with existing arabic name', function () {

    $admin = createSubscriptionPlanAdmin();

    createSubscriptionPlan([
        'name' => [
            'en' => 'Existing Plan',
            'ar' => 'الخطة الموجودة',
        ],
    ]);

    $plan = createSubscriptionPlan([
        'name' => [
            'en' => 'Current Plan',
            'ar' => 'الخطة الحالية',
        ],
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/central/subscriptionplans/{$plan->id}", [
            'name_en' => 'Updated Plan',
            'name_ar' => 'الخطة الموجودة',
            'is_active' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_ar']);
});

test('subscription plan can be updated without changing its own name', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan([
        'name' => [
            'en' => 'Current Plan',
            'ar' => 'الخطة الحالية',
        ],
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/central/subscriptionplans/{$plan->id}", [
            'name_en' => 'Current Plan',
            'name_ar' => 'الخطة الحالية',
            'description_en' => 'New description',
            'description_ar' => 'وصف جديد',
            'is_active' => true,
        ]);

    $response->assertStatus(200);
});


/*
|--------------------------------------------------------------------------
| Soft Delete
|--------------------------------------------------------------------------
*/

test('admin can delete a subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/subscriptionplans/{$plan->id}");

    $response->assertStatus(200);

    expect(
        SubscriptionPlan::withTrashed()->find($plan->id)->trashed()
    )->toBeTrue();
});

test('user without permission cannot delete a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/central/subscriptionplans/{$plan->id}");

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Trashed Plans
|--------------------------------------------------------------------------
*/

test('admin can get all trashed subscription plans', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/subscriptionplans/trashed');

    $response->assertStatus(200);
});

test('admin can get a specific trashed subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/subscriptionplans/{$plan->id}/trashed");

    $response->assertStatus(200);
});

test('cannot get a non trashed subscription plan through trashed endpoint', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/subscriptionplans/{$plan->id}/trashed");

    $response->assertStatus(403);
});

test('user without permission cannot view trashed subscription plans', function () {

    $user = createSubscriptionPlanUser();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/central/subscriptionplans/trashed');

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

test('admin can restore a trashed subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/central/subscriptionplans/{$plan->id}/restore");

    $response->assertStatus(200);

    expect(
        SubscriptionPlan::find($plan->id)
    )->not->toBeNull();
});

test('user without permission cannot restore a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/central/subscriptionplans/{$plan->id}/restore");

    $response->assertStatus(403);
});

test('cannot restore a non trashed subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/central/subscriptionplans/{$plan->id}/restore");

    $response->assertStatus(403);
});

test('admin can restore all trashed subscription plans', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan1 = createSubscriptionPlan();
    $plan2 = createSubscriptionPlan();

    $plan1->delete();
    $plan2->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans/restore-all');

    $response->assertStatus(200);

    expect(SubscriptionPlan::find($plan1->id))->not->toBeNull()
        ->and(SubscriptionPlan::find($plan2->id))->not->toBeNull();
});

test('cannot restore all subscription plans when trash is empty', function () {

    $admin = createSubscriptionPlanAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/subscriptionplans/restore-all');

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Force Delete
|--------------------------------------------------------------------------
*/

test('admin can force delete a trashed subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/subscriptionplans/{$plan->id}/force-delete");

    $response->assertStatus(200);

    expect(
        SubscriptionPlan::withTrashed()->find($plan->id)
    )->toBeNull();
});

test('user without permission cannot force delete a subscription plan', function () {

    $user = createSubscriptionPlanUser();

    $plan = createSubscriptionPlan();

    $plan->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/central/subscriptionplans/{$plan->id}/force-delete");

    $response->assertStatus(403);
});

test('cannot force delete a non trashed subscription plan', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan = createSubscriptionPlan();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/subscriptionplans/{$plan->id}/force-delete");

    $response->assertStatus(403);
});

test('admin can force delete all trashed subscription plans', function () {

    $admin = createSubscriptionPlanAdmin();

    $plan1 = createSubscriptionPlan();
    $plan2 = createSubscriptionPlan();

    $plan1->delete();
    $plan2->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/central/subscriptionplans/force-delete-all');

    $response->assertStatus(200);

    expect(SubscriptionPlan::withTrashed()->find($plan1->id))->toBeNull()
        ->and(SubscriptionPlan::withTrashed()->find($plan2->id))->toBeNull();
});

test('cannot force delete all subscription plans when trash is empty', function () {

    $admin = createSubscriptionPlanAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/central/subscriptionplans/force-delete-all');

    $response->assertStatus(403);
});
