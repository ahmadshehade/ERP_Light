<?php

namespace Tests\Unit\Modules\Tenant\Policies;

use App\Models\User;
use Mockery;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Policies\TeamPolicy;
use Tests\TestCase;

class TeamPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function makePolicy(TenantUser $tenantUser): TeamPolicy
    {
        $policy = Mockery::mock(TeamPolicy::class)->makePartial();

        $policy->shouldReceive('getTenantUser')
            ->andReturn($tenantUser);

        return $policy;
    }

    public function test_owner_can_view_any_team(): void
    {
        $user = new User();
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->viewAny($user)
        );
    }

    public function test_manager_can_view_any_team(): void
    {
        $user = new User();
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->viewAny($user)
        );
    }

    public function test_employee_cannot_view_any_team(): void
    {
        $user = new User();
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->viewAny($user)
        );
    }

    public function test_user_with_view_team_permission_can_view_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewTeam->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->view($user, $team)
        );
    }

    public function test_user_without_view_team_permission_cannot_view_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewTeam->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->view($user, $team)
        );
    }

    public function test_owner_can_create_team(): void
    {
        $user = new User();
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->create($user)
        );
    }

    public function test_employee_cannot_create_team(): void
    {
        $user = new User();
        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->create($user)
        );
    }

    public function test_manager_can_update_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->update($user, $team)
        );
    }

    public function test_employee_cannot_update_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->update($user, $team)
        );
    }

    public function test_manager_can_delete_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->delete($user, $team)
        );
    }

    public function test_employee_cannot_delete_team(): void
    {
        $user = new User();
        $team = new Team();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasRole')
            ->with([
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ])
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->delete($user, $team)
        );
    }
}
