<?php

use App\Enums\NameOfRoles;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    Role::findOrCreate(
        NameOfRoles::SuperAdmin->value,
        'web'
    );

    Role::findOrCreate(
        NameOfRoles::Owner->value,
        'web'
    );

    Role::findOrCreate(
        NameOfRoles::Guest->value,
        'web'
    );
});


it('allows super admin to view the dashboard', function () {
    $user = User::factory()->create();

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    actingAs($user);

    getJson('/api/v1/central/super-admin/dashboard')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'users' => [
                    'total',
                    'active',
                    'deleted',
                    'verified',
                    'unverified',
                    'new',
                ],

                'profiles' => [
                    'total',
                    'completed',
                    'incomplete',
                ],

                'companies' => [
                    'total',
                    'active',
                    'deleted',
                    'enabled',
                    'disabled',
                    'new',
                ],

                'tenants' => [
                    'total',
                    'new',
                ],

                'subscription_plans' => [
                    'total',
                    'active',
                    'inactive',
                    'deleted',
                    'new',
                ],

                'subscription_prices' => [
                    'total',
                    'active',
                    'inactive',
                    'deleted',
                    'with_trial',
                    'without_trial',
                    'intervals' => [
                        'day',
                        'week',
                        'month',
                        'year',
                    ],
                ],

                'subscriptions' => [
                    'total',
                    'active_records',
                    'deleted',
                    'active',
                    'pending',
                    'canceled',
                    'expired',
                    'trial',
                    'trial_expired',
                    'new',
                ],

                'payments' => [
                    'total',
                    'active_records',
                    'deleted',
                    'pending',
                    'paid',
                    'failed',
                    'refunded',
                    'canceled',
                    'new',
                ],

                'revenue' => [
                    'currencies',
                ],

                'activity' => [
                    'total',
                    'last_30_days',
                ],
            ],
        ]);
});


it('returns correct user statistics', function () {
    $superAdmin = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $superAdmin->assignRole(NameOfRoles::SuperAdmin->value);

    User::factory()->create([
        'email_verified_at' => now(),
    ]);

    User::factory()->create([
        'email_verified_at' => null,
    ]);

    $deletedUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $deletedUser->delete();

    actingAs($superAdmin);

    getJson('/api/v1/central/super-admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.users.total', 4)
        ->assertJsonPath('data.users.active', 3)
        ->assertJsonPath('data.users.deleted', 1)
        ->assertJsonPath('data.users.verified', 2)
        ->assertJsonPath('data.users.unverified', 1);
});


it('returns correct profile statistics', function () {
    $superAdmin = User::factory()->create();

    $superAdmin->assignRole(NameOfRoles::SuperAdmin->value);

    $completedUser = User::factory()->create();

    $completedProfile = new Profile();

    $completedProfile->user_id = $completedUser->id;
    $completedProfile->phone = '123456789';
    $completedProfile->timezone = 'UTC';
    $completedProfile->language = 'en';
    $completedProfile->birth_date = '1990-01-01';

    $completedProfile->save();

    $incompleteUser = User::factory()->create();

    $incompleteProfile = new Profile();

    $incompleteProfile->user_id = $incompleteUser->id;
    $incompleteProfile->phone = '987654321';
    $incompleteProfile->timezone = 'UTC';
    $incompleteProfile->language = 'en';
    $incompleteProfile->birth_date = null;

    $incompleteProfile->save();

    actingAs($superAdmin);

    getJson('/api/v1/central/super-admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.profiles.total', 2)
        ->assertJsonPath('data.profiles.completed', 1)
        ->assertJsonPath('data.profiles.incomplete', 1);
});


it('does not allow guest to view the super admin dashboard', function () {
    $guest = User::factory()->create();

    $guest->assignRole(NameOfRoles::Guest->value);

    actingAs($guest);

    getJson('/api/v1/central/super-admin/dashboard')
        ->assertForbidden();
});


it('requires authentication to view the super admin dashboard', function () {
    getJson('/api/v1/central/super-admin/dashboard')
        ->assertUnauthorized();
});
