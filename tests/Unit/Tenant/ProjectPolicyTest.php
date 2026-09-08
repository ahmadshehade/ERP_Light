<?php

namespace Tests\Unit\Tenant;

use App\Models\User;
use Mockery;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Policies\ProjectPolicy;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function makePolicy(TenantUser $tenantUser): ProjectPolicy
    {
        $policy = Mockery::mock(ProjectPolicy::class)
            ->makePartial();

        $policy->shouldReceive('getTenantUser')
            ->andReturn($tenantUser);

        return $policy;
    }

    /*
    |--------------------------------------------------------------------------
    | viewAny
    |--------------------------------------------------------------------------
    */

    public function test_user_with_view_any_projects_permission_can_view_any_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewAnyProjects->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->viewAny($user)
        );
    }

    public function test_user_without_view_any_projects_permission_cannot_view_any_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewAnyProjects->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->viewAny($user)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | view
    |--------------------------------------------------------------------------
    */

    public function test_user_with_view_project_permission_can_view_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->view($user, $project)
        );
    }

    public function test_user_without_view_project_permission_cannot_view_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantViewProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->view($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_user_with_create_project_permission_can_create_project(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCreateProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->create($user)
        );
    }

    public function test_user_without_create_project_permission_cannot_create_project(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCreateProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->create($user)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_user_with_update_project_permission_can_update_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantUpdateProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->update($user, $project)
        );
    }

    public function test_user_without_update_project_permission_cannot_update_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantUpdateProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->update($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_user_with_delete_project_permission_can_delete_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantDeleteProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->delete($user, $project)
        );
    }

    public function test_user_without_delete_project_permission_cannot_delete_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantDeleteProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->delete($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | restore
    |--------------------------------------------------------------------------
    */

    public function test_user_with_restore_project_permission_can_restore_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantRestoreProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->restore($user, $project)
        );
    }

    public function test_user_without_restore_project_permission_cannot_restore_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantRestoreProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->restore($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | forceDelete
    |--------------------------------------------------------------------------
    */

    public function test_user_with_force_delete_project_permission_can_force_delete_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantForceDeleteProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->forceDelete($user, $project)
        );
    }

    public function test_user_without_force_delete_project_permission_cannot_force_delete_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantForceDeleteProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->forceDelete($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | getTrashed
    |--------------------------------------------------------------------------
    */

    public function test_user_with_get_trashed_permission_can_get_trashed_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantGetTrashedProjects->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->getTrashed($user, $project)
        );
    }

    public function test_user_without_get_trashed_permission_cannot_get_trashed_project(): void
    {
        $user = new User();
        $project = new Project();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantGetTrashedProjects->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->getTrashed($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | getAllTrashed
    |--------------------------------------------------------------------------
    */

    public function test_user_with_get_all_trashed_permission_can_get_all_trashed_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantGetAllTrashedProjects->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->getAllTrashed($user)
        );
    }

    public function test_user_without_get_all_trashed_permission_cannot_get_all_trashed_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantGetAllTrashedProjects->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->getAllTrashed($user)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | forceDeleteAll
    |--------------------------------------------------------------------------
    */

    public function test_user_with_force_delete_all_permission_can_force_delete_all_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantForceDeleteAllProjects->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->forceDeleteAll($user)
        );
    }

    public function test_user_without_force_delete_all_permission_cannot_force_delete_all_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantForceDeleteAllProjects->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->forceDeleteAll($user)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | restoreAll
    |--------------------------------------------------------------------------
    */

    public function test_user_with_restore_all_permission_can_restore_all_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantRestoreAllProjects->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->restoreAll($user)
        );
    }

    public function test_user_without_restore_all_permission_cannot_restore_all_projects(): void
    {
        $user = new User();

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantRestoreAllProjects->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->restoreAll($user)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | onHold
    |--------------------------------------------------------------------------
    */

    public function test_user_can_put_in_progress_project_on_hold(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantOnHoldProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->onHold($user, $project)
        );
    }

    public function test_user_cannot_put_project_on_hold_without_permission(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantOnHoldProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->onHold($user, $project)
        );
    }

    public function test_user_cannot_put_project_on_hold_when_project_is_not_in_progress(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::Planned;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantOnHoldProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->onHold($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | complete
    |--------------------------------------------------------------------------
    */

    public function test_user_can_complete_in_progress_project(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCompleteProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->complete($user, $project)
        );
    }

    public function test_user_cannot_complete_project_without_permission(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCompleteProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->complete($user, $project)
        );
    }

    public function test_user_cannot_complete_project_when_project_is_not_in_progress(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::Planned;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCompleteProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->complete($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | resume
    |--------------------------------------------------------------------------
    */

    public function test_user_can_resume_on_hold_project(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::OnHold;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantResumeProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->resume($user, $project)
        );
    }

    public function test_user_cannot_resume_project_without_permission(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::OnHold;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantResumeProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->resume($user, $project)
        );
    }

    public function test_user_cannot_resume_project_when_project_is_not_on_hold(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantResumeProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->resume($user, $project)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | cancel
    |--------------------------------------------------------------------------
    */

    public function test_user_can_cancel_in_progress_project(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCancelProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertTrue(
            $policy->cancel($user, $project)
        );
    }

    public function test_user_cannot_cancel_project_without_permission(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::InProgress;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCancelProject->value)
            ->once()
            ->andReturn(false);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->cancel($user, $project)
        );
    }

    public function test_user_cannot_cancel_project_when_project_is_not_in_progress(): void
    {
        $user = new User();
        $project = new Project();

        $project->status = ProjectStatus::Planned;

        $tenantUser = Mockery::mock(TenantUser::class);

        $tenantUser->shouldReceive('hasPermissionTo')
            ->with(TenantPermission::TenantCancelProject->value)
            ->once()
            ->andReturn(true);

        $policy = $this->makePolicy($tenantUser);

        $this->assertFalse(
            $policy->cancel($user, $project)
        );
    }
}
