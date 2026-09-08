<?php

namespace Tests\Unit\Modules\Tenant\Services\Project;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Services\Project\ProjectNotificationService;
use Modules\Tenant\Services\Project\ProjectStatusService;
use Mockery;
use Spatie\Activitylog\Facades\Activity;
use Tests\TestCase;

class ProjectStatusServiceTest extends TestCase
{
    private ProjectStatusService $service;

    private ProjectNotificationService $notify;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * ------------------------------------------------------------
         * Tenant connection
         * ------------------------------------------------------------
         *
         * We provide a tenant connection using SQLite in-memory.
         *
         * This does NOT connect to your real tenant database.
         */
        config([
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('tenant');

        /*
         * ------------------------------------------------------------
         * Disable ActivityLog
         * ------------------------------------------------------------
         *
         * ProjectStatusService writes an activity after commit.
         * We don't want that to create database queries.
         */
        activity()->disableLogging();

        /*
         * ------------------------------------------------------------
         * Fake Cache
         * ------------------------------------------------------------
         */
        Cache::shouldReceive('tags')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        /*
         * ------------------------------------------------------------
         * Fake Notifications
         * ------------------------------------------------------------
         */
        $this->notify = Mockery::mock(
            ProjectNotificationService::class
        );

        $this->notify
            ->shouldReceive('startProjectNotify')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive('onHoldProjectNotify')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive('resumeProjectNotify')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive('cancelProjectNotify')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive('completeProjectNotify')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        /*
         * Use the REAL ProjectStatusService.
         *
         * No modification is required in the service.
         */
        $this->service = new ProjectStatusService(
            $this->notify
        );
    }

    protected function tearDown(): void
    {
        /*
         * Re-enable ActivityLog for other tests.
         */
        activity()->enableLogging();

        DB::purge('tenant');

        Mockery::close();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function makeProject(ProjectStatus $status): Project
    {
        $project = new Project();

        $project->status = $status;
        $project->start_date = null;
        $project->end_date = null;

        return $project;
    }

    private function mockSaveAndRefresh(Project $project): Project
    {
        $project = Mockery::mock($project)->makePartial();

        $project
            ->shouldReceive('save')
            ->once()
            ->andReturnTrue();

        $project
            ->shouldReceive('refresh')
            ->once()
            ->andReturnSelf();

        return $project;
    }

    /*
    |--------------------------------------------------------------------------
    | START
    |--------------------------------------------------------------------------
    */

    public function test_start_changes_planned_project_to_in_progress(): void
    {
        $project = $this->makeProject(
            ProjectStatus::Planned
        );

        $project = $this->mockSaveAndRefresh($project);

        $result = $this->service->start($project);

        $this->assertSame(
            ProjectStatus::InProgress,
            $result->status
        );

        $this->assertNotNull(
            $result->start_date
        );

        $this->assertNull(
            $result->end_date
        );
    }

    public function test_start_throws_exception_when_project_is_not_planned(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Project status is not planned.'
        );

        $this->service->start($project);
    }

    /*
    |--------------------------------------------------------------------------
    | HOLD
    |--------------------------------------------------------------------------
    */

    public function test_hold_changes_in_progress_project_to_on_hold(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        $project = $this->mockSaveAndRefresh($project);

        $result = $this->service->hold($project);

        $this->assertSame(
            ProjectStatus::OnHold,
            $result->status
        );

        $this->assertNull(
            $result->end_date
        );
    }

    public function test_hold_throws_exception_when_project_is_not_in_progress(): void
    {
        $project = $this->makeProject(
            ProjectStatus::Planned
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only in-progress projects can be put on hold.'
        );

        $this->service->hold($project);
    }

    /*
    |--------------------------------------------------------------------------
    | RESUME
    |--------------------------------------------------------------------------
    */

    public function test_resume_changes_on_hold_project_to_in_progress(): void
    {
        $project = $this->makeProject(
            ProjectStatus::OnHold
        );

        $project = $this->mockSaveAndRefresh($project);

        $result = $this->service->resume($project);

        $this->assertSame(
            ProjectStatus::InProgress,
            $result->status
        );

        $this->assertNull(
            $result->end_date
        );
    }

    public function test_resume_throws_exception_when_project_is_not_on_hold(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only on-hold projects can be resumed.'
        );

        $this->service->resume($project);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function test_cancel_changes_in_progress_project_to_cancelled(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        /*
         * IMPORTANT:
         *
         * Project::tasks() returns HasMany.
         * Therefore the mock must be HasMany.
         */
        $tasks = Mockery::mock(HasMany::class);

        $tasks
            ->shouldReceive('whereNotIn')
            ->once()
            ->with(
                'status',
                [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ]
            )
            ->andReturnSelf();

        $tasks
            ->shouldReceive('update')
            ->once()
            ->with([
                'status' => TaskStatus::CANCELLED->value,
            ])
            ->andReturn(1);

        $project = Mockery::mock($project)->makePartial();

        $project
            ->shouldReceive('tasks')
            ->once()
            ->andReturn($tasks);

        $project
            ->shouldReceive('save')
            ->once()
            ->andReturnTrue();

        $project
            ->shouldReceive('refresh')
            ->once()
            ->andReturnSelf();

        $result = $this->service->cancel($project);

        $this->assertSame(
            ProjectStatus::Cancelled,
            $result->status
        );

        $this->assertNotNull(
            $result->end_date
        );
    }

    public function test_cancel_throws_exception_when_project_is_not_in_progress(): void
    {
        $project = $this->makeProject(
            ProjectStatus::Planned
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only in-progress projects can be cancelled.'
        );

        $this->service->cancel($project);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE
    |--------------------------------------------------------------------------
    */

    public function test_complete_changes_project_to_completed_when_all_tasks_are_completed_or_cancelled(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        /*
         * Project::tasks() must return HasMany.
         */
        $tasks = Mockery::mock(HasMany::class);

        $tasks
            ->shouldReceive('whereNotIn')
            ->once()
            ->with(
                'status',
                [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ]
            )
            ->andReturnSelf();

        $tasks
            ->shouldReceive('exists')
            ->once()
            ->andReturnFalse();

        $project = Mockery::mock($project)->makePartial();

        $project
            ->shouldReceive('tasks')
            ->once()
            ->andReturn($tasks);

        $project
            ->shouldReceive('save')
            ->once()
            ->andReturnTrue();

        $project
            ->shouldReceive('refresh')
            ->once()
            ->andReturnSelf();

        $result = $this->service->complete($project);

        $this->assertSame(
            ProjectStatus::Completed,
            $result->status
        );

        $this->assertNotNull(
            $result->end_date
        );
    }

    public function test_complete_throws_exception_when_project_has_incomplete_tasks(): void
    {
        $project = $this->makeProject(
            ProjectStatus::InProgress
        );

        /*
         * Project::tasks() must return HasMany.
         */
        $tasks = Mockery::mock(HasMany::class);

        $tasks
            ->shouldReceive('whereNotIn')
            ->once()
            ->with(
                'status',
                [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ]
            )
            ->andReturnSelf();

        $tasks
            ->shouldReceive('exists')
            ->once()
            ->andReturnTrue();

        $project = Mockery::mock($project)->makePartial();

        $project
            ->shouldReceive('tasks')
            ->once()
            ->andReturn($tasks);

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Project cannot be completed because it has incomplete tasks.'
        );

        $this->service->complete($project);
    }

    public function test_complete_throws_exception_when_project_is_not_in_progress(): void
    {
        $project = $this->makeProject(
            ProjectStatus::Planned
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only in-progress projects can be completed.'
        );

        $this->service->complete($project);
    }
}
