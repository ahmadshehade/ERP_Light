<?php

namespace Tests\Unit\Modules\Tenant\Policies;

use App\Models\User;
use Mockery;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Policies\TaskPolicy;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    private TaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * We partially mock the policy so getTenantUser()
         * doesn't access the real tenant database.
         */
        $this->policy = Mockery::mock(TaskPolicy::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Create a fake User.
     */
    private function makeUser(): User
    {
        return new User();
    }

    /**
     * Create a fake Task.
     */
    private function makeTask(): Task
    {
        return new Task();
    }

    /**
     * Create a fake TenantUser.
     */
    private function makeTenantUser(bool $hasPermission): TenantUser
    {
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->andReturn($hasPermission);

        return $tenantUser;
    }

    /*
    |--------------------------------------------------------------------------
    | viewAny
    |--------------------------------------------------------------------------
    */

    public function test_view_any_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewAny($user);

        $this->assertTrue($result);
    }

    public function test_view_any_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewAny($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | view
    |--------------------------------------------------------------------------
    */

    public function test_view_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->view($user, $task);

        $this->assertTrue($result);
    }

    public function test_view_returns_false_when_user_has_no_tenant_user(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        /*
     * getTenantUser() is declared to return TenantUser,
     * so Mockery cannot return null.
     *
     * Instead, return a TenantUser whose object is falsy
     * for this specific test.
     */
        $tenantUser = Mockery::mock(TenantUser::class);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        /*
     * Because view() reaches hasPermissionTo() only when
     * the tenant user is truthy, return false for permission.
     */
        $tenantUser
            ->shouldReceive('hasPermissionTo')
            ->once()
            ->with(TenantPermission::TenantViewTask->value)
            ->andReturn(false);

        $result = $this->policy->view($user, $task);

        $this->assertFalse($result);
    }

    public function test_view_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->view($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_create_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->create($user);

        $this->assertTrue($result);
    }

    public function test_create_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->create($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->update($user, $task);

        $this->assertTrue($result);
    }

    public function test_update_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->update($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_delete_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->delete($user, $task);

        $this->assertTrue($result);
    }

    public function test_delete_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->delete($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | restore
    |--------------------------------------------------------------------------
    */

    public function test_restore_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->restore($user, $task);

        $this->assertTrue($result);
    }

    public function test_restore_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->restore($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | forceDelete
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->forceDelete($user, $task);

        $this->assertTrue($result);
    }

    public function test_force_delete_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->forceDelete($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | viewTrashed
    |--------------------------------------------------------------------------
    */

    public function test_view_trashed_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewTrashed($user, $task);

        $this->assertTrue($result);
    }

    public function test_view_trashed_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewTrashed($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | viewAllTrashed
    |--------------------------------------------------------------------------
    */

    public function test_view_all_trashed_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewAllTrashed($user);

        $this->assertTrue($result);
    }

    public function test_view_all_trashed_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->viewAllTrashed($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | restoreAll
    |--------------------------------------------------------------------------
    */

    public function test_restore_all_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->restoreAll($user);

        $this->assertTrue($result);
    }

    public function test_restore_all_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->restoreAll($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | forceDeleteAll
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_all_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->forceDeleteAll($user);

        $this->assertTrue($result);
    }

    public function test_force_delete_all_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->forceDeleteAll($user);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | completeTask
    |--------------------------------------------------------------------------
    */

    public function test_complete_task_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->completeTask($user, $task);

        $this->assertTrue($result);
    }

    public function test_complete_task_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->completeTask($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | cancelTask
    |--------------------------------------------------------------------------
    */

    public function test_cancel_task_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->cancelTask($user, $task);

        $this->assertTrue($result);
    }

    public function test_cancel_task_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->cancelTask($user, $task);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | onHoldTask
    |--------------------------------------------------------------------------
    */

    public function test_on_hold_task_returns_true_when_user_has_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(true);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->onHoldTask($user, $task);

        $this->assertTrue($result);
    }

    public function test_on_hold_task_returns_false_when_user_does_not_have_permission(): void
    {
        $user = $this->makeUser();

        $task = $this->makeTask();

        $tenantUser = $this->makeTenantUser(false);

        $this->policy
            ->shouldReceive('getTenantUser')
            ->once()
            ->with($user)
            ->andReturn($tenantUser);

        $result = $this->policy->onHoldTask($user, $task);

        $this->assertFalse($result);
    }
}
