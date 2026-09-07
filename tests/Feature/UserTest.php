<?php

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

uses(DatabaseTransactions::class);

function createUserManagementAdmin(): User
{
    $user = User::create([
        'name' => 'User Management Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createTestUser(): User
{
    return User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

test('unauthenticated user cannot access users', function () {

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

test('admin can get all users', function () {

    $admin = createUserManagementAdmin();

    createTestUser();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
});

test('admin can get a specific user', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->getJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Update User
|--------------------------------------------------------------------------
*/

test('admin can update a user', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Updated User',
            'email' => 'updated-user@example.com',
        ]);

    $response->assertStatus(200);

    $user->refresh();

    expect($user->name)->toBe('Updated User')
        ->and($user->email)->toBe('updated-user@example.com');
});

test('admin can update user password', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'password' => 'NewP@ssword123',
            'password_confirmation' => 'NewP@ssword123',
        ]);

    $response->assertStatus(200);

    $user->refresh();

    expect(
        Hash::check('NewP@ssword123', $user->password)
    )->toBeTrue();
});

test('admin cannot update user with an existing email', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $anotherUser = createTestUser();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'email' => $anotherUser->email,
        ]);

    $response->assertStatus(422);
});

test('admin cannot update user with an invalid password', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

    $response->assertStatus(422);
});

test('admin cannot update user with unconfirmed password', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'password' => 'NewP@ssword123',
            'password_confirmation' => 'WrongP@ssword123',
        ]);

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Soft Delete
|--------------------------------------------------------------------------
*/

test('admin can soft delete a user', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200);

    expect(User::withTrashed()->find($user->id)->trashed())
        ->toBeTrue();
});

test('admin can get trashed users', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $user->delete();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/users/trashed-users');

    $response->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

test('admin can restore a trashed user', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $user->delete();

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/users/restore/{$user->id}");

    $response->assertStatus(200);

    expect(User::find($user->id))->not->toBeNull();
});

test('admin can restore all trashed users', function () {

    $admin = createUserManagementAdmin();

    $user1 = createTestUser();
    $user2 = createTestUser();

    $user1->delete();
    $user2->delete();

    $response = $this->actingAs($admin)
        ->putJson('/api/v1/users/restore_all');

    $response->assertStatus(200);

    expect(User::find($user1->id))->not->toBeNull()
        ->and(User::find($user2->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Force Delete
|--------------------------------------------------------------------------
*/

test('admin can permanently delete a trashed user', function () {

    $admin = createUserManagementAdmin();

    $user = createTestUser();

    $userId = $user->id;

    $user->delete();

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/users/force_delete/{$userId}");


    $response->assertStatus(200);

    expect(User::withTrashed()->find($userId))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Empty Trash
|--------------------------------------------------------------------------
*/

test('admin can permanently delete all trashed users', function () {

    $admin = createUserManagementAdmin();

    $user1 = createTestUser();
    $user2 = createTestUser();

    $user1Id = $user1->id;
    $user2Id = $user2->id;

    $user1->delete();
    $user2->delete();

    $response = $this->actingAs($admin)
        ->deleteJson('/api/v1/users/empty_trash');


    $response->assertStatus(200);

    expect(User::withTrashed()->find($user1Id))->toBeNull()
        ->and(User::withTrashed()->find($user2Id))->toBeNull();
});
