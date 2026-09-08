<?php

namespace Tests\Unit\Tenant;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Mockery;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Policies\PositionPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PositionPolicyTest extends TestCase
{
    private MockPositionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new MockPositionPolicy();
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

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

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
    | 2. Create
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_with_create_permission_can_create_position(): void
    {
        $user = new User();
        $user->id = 1;

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantCreatePosition->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->create($user);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Update
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_with_update_permission_can_update_position(): void
    {
        $user = new User();
        $user->id = 1;

        $position = new Position();
        $position->id = 1;

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantUpdatePosition->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->update(
            $user,
            $position
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Delete
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_without_delete_permission_cannot_delete_position(): void
    {
        $user = new User();
        $user->id = 1;

        $position = new Position();
        $position->id = 1;

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantDeletePosition->value
            )
            ->andReturn(false);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->delete(
            $user,
            $position
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Restore
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_with_restore_permission_can_restore_position(): void
    {
        $user = new User();
        $user->id = 1;

        $position = new Position();
        $position->id = 1;

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantRestorePosition->value
            )
            ->andReturn(true);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->restore(
            $user,
            $position
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Force Delete
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function user_without_force_delete_permission_cannot_force_delete_position(): void
    {
        $user = new User();
        $user->id = 1;

        $position = new Position();
        $position->id = 1;

        $tenantUser = Mockery::mock(
            \Modules\Tenant\Models\TenantUser::class
        );

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(
                TenantPermission::TenantForceDeletePosition->value
            )
            ->andReturn(false);

        $this->policy->setTenantUser($tenantUser);

        $result = $this->policy->forceDelete(
            $user,
            $position
        );

        $this->assertFalse($result);
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

class MockPositionPolicy extends PositionPolicy
{
    private ?\Modules\Tenant\Models\TenantUser $tenantUser = null;

    public function setTenantUser(
        \Modules\Tenant\Models\TenantUser $tenantUser
    ): void {
        $this->tenantUser = $tenantUser;
    }

    public function getTenantUser(User $user): \Modules\Tenant\Models\TenantUser
    {
        return $this->tenantUser;
    }
}
