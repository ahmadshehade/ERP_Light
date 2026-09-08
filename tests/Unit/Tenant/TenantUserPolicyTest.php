<?php

namespace Tests\Unit\Tenant;

use App\Models\User;
use Mockery;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Policies\TenantUserPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantUserPolicyTest extends TestCase
{
    private MockTenantUserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new MockTenantUserPolicy();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Owner
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function owner_can_do_any_action(): void
    {
        $user = new User();
        $user->id = 1;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasRole')
            ->once()
            ->with(TenantRoles::Owner->value)
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->before($user);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. User with permission can create
    |--------------------------------------------------------------------------
    */

    #[Test]

    public function user_with_create_permission_can_create_user(): void
    {
        $user = new User();
        $user->id = 1;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantCreateUser->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->create($user);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. User without permission is rejected
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_without_create_permission_cannot_create_user(): void
    {
        $user = new User();
        $user->id = 1;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantCreateUser->value
            )
            ->andReturn(false);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->create($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. User with permission can update
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_with_update_permission_can_update_user(): void
    {
        $user = new User();
        $user->id = 1;

        $model = Mockery::mock(TenantUser::class);

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantUpdateUser->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->update(
            $user,
            $model
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. User without permission cannot delete
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_without_delete_permission_cannot_delete_user(): void
    {
        $user = new User();
        $user->id = 1;

        $model = Mockery::mock(TenantUser::class);

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantDeleteUser->value
            )
            ->andReturn(false);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->delete(
            $user,
            $model
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. View
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_can_view_his_own_tenant_user_when_permission_exists(): void
    {
        $user = new User();
        $user->id = 1;

        $model = Mockery::mock(TenantUser::class);

        $model
            ->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantViewUser->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->view(
            $user,
            $model
        );

        $this->assertTrue($result);
    }
}


/*
|--------------------------------------------------------------------------
| Test Policy
|--------------------------------------------------------------------------
|
| Prevents TenantUser::where() from reaching the database.
|
*/

class MockTenantUserPolicy extends TenantUserPolicy
{
    private ?TenantUser $tenantUser = null;

    public function setTenantUser(TenantUser $tenantUser): void
    {
        $this->tenantUser = $tenantUser;
    }

    public function getTenantUser(User $user): TenantUser
    {
        return $this->tenantUser;
    }
}
