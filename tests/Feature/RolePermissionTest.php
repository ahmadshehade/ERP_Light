<?php

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

function createAdmin(): User
{
    $user = User::create([
        'name' => 'Role Permission Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

test('admin can get all roles', function () {

    $admin = createAdmin();

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/v1/roles');

    $response->assertStatus(200);
});

test('admin can get a specific role with permissions', function () {

    $admin = createAdmin();

    $role = Role::create([
        'name' => 'Test Manager',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/v1/roles/{$role->id}");

    $response->assertStatus(200);
});

test('admin can assign a role to a user', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $role = Role::create([
        'name' => 'Test Employee',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/roles/assign/users/{$user->id}/role/{$role->id}"
        );

    $response->assertStatus(200);

    expect($user->fresh()->hasRole($role))->toBeTrue();
});

test('admin can remove a role from a user', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $role = Role::create([
        'name' => 'Test Employee',
        'guard_name' => 'web',
    ]);

    $user->assignRole($role);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/roles/remove/users/{$user->id}/role/{$role->id}"
        );

    $response->assertStatus(200);

    expect($user->fresh()->hasRole($role))->toBeFalse();
});

test('admin can sync roles to a user', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $role1 = Role::create([
        'name' => 'Test Manager',
        'guard_name' => 'web',
    ]);

    $role2 = Role::create([
        'name' => 'Test Employee',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/roles/sync/users/{$user->id}",
            [
                'roles' => [
                    $role1->id,
                    $role2->id,
                ],
            ]
        );

    $response->assertStatus(200);

    $user->refresh();

    expect($user->hasRole($role1))->toBeTrue()
        ->and($user->hasRole($role2))->toBeTrue();
});

test('super admin role cannot be removed by syncing other roles', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Super Admin User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $superAdminRole = Role::where(
        'name',
        NameOfRoles::SuperAdmin->value
    )->firstOrFail();

    $managerRole = Role::create([
        'name' => 'Test Manager',
        'guard_name' => 'web',
    ]);

    $user->assignRole($superAdminRole);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/roles/sync/users/{$user->id}",
            [
                'roles' => [
                    $managerRole->id,
                ],
            ]
        );

    $response->assertStatus(200);

    $user->refresh();

    expect($user->hasRole($superAdminRole))->toBeTrue()
        ->and($user->hasRole($managerRole))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
*/

test('admin can get all permissions', function () {

    $admin = createAdmin();

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/v1/permissions');

    $response->assertStatus(200);
});

test('admin can get a specific permission with roles', function () {

    $admin = createAdmin();

    $permission = Permission::create([
        'name' => 'test permission',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/v1/permissions/{$permission->id}");

    $response->assertStatus(200);
});

test('admin can sync roles to a permission', function () {

    $admin = createAdmin();

    $permission = Permission::create([
        'name' => 'test permission',
        'guard_name' => 'web',
    ]);

    $role1 = Role::create([
        'name' => 'Test Manager',
        'guard_name' => 'web',
    ]);

    $role2 = Role::create([
        'name' => 'Test Employee',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/permissions/sync/permission/{$permission->id}",
            [
                'roles' => [
                    $role1->id,
                    $role2->id,
                ],
            ]
        );

    $response->assertStatus(200);

    $permission->refresh();

    expect($permission->roles->contains($role1))->toBeTrue()
        ->and($permission->roles->contains($role2))->toBeTrue();
});

test('admin can give permission to a user', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $permission = Permission::create([
        'name' => 'test permission',
        'guard_name' => 'web',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/permissions/assign/users/{$user->id}/permission/{$permission->id}"
        );

    $response->assertStatus(200);

    expect(
        $user->fresh()->hasPermissionTo($permission)
    )->toBeTrue();
});

test('admin can revoke permission from a user', function () {

    $admin = createAdmin();

    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $permission = Permission::create([
        'name' => 'test permission',
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    $response = $this->actingAs($admin)
        ->postJson(
            "/api/admin/v1/permissions/remove/users/{$user->id}/permission/{$permission->id}"
        );

    $response->assertStatus(200);

    expect(
        $user->fresh()->hasPermissionTo($permission)
    )->toBeFalse();
});
