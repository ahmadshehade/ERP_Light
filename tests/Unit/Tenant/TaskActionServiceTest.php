<?php

namespace Tests\Unit\Modules\Tenant\Services\Task;

use App\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Services\Task\TaskActionService;
use Modules\Tenant\Services\Task\TaskNotificationService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaskActionServiceTest extends TestCase
{
    private TaskActionService $service;

    private TaskNotificationService $notify;

    private $tenantConnection;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Create physical SQLite database for the test
        |--------------------------------------------------------------------------
        */

        $directory = storage_path(
            'framework/testing'
        );

        if (! is_dir($directory)) {
            mkdir(
                $directory,
                0777,
                true
            );
        }

        $this->databaseFile = $directory
            . '/task_action_service.sqlite';

        /*
         * Remove previous test database.
         */
        if (file_exists($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        /*
         * SQLite requires the file to exist.
         */
        touch($this->databaseFile);

        /*
        |--------------------------------------------------------------------------
        | Database configuration
        |--------------------------------------------------------------------------
        */

        config([
            'database.default' => 'tenant',

            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => $this->databaseFile,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('tenant');

        /*
        |--------------------------------------------------------------------------
        | Create tenant_users
        |--------------------------------------------------------------------------
        */

        Schema::connection('tenant')->create(
            'tenant_users',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'user_id'
                );

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();

                $table->unique(
                    'user_id'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Create roles
        |--------------------------------------------------------------------------
        */

        Schema::connection('tenant')->create(
            'roles',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'name'
                );

                $table->string(
                    'guard_name'
                );

                $table->timestamps();

                $table->unique([
                    'name',
                    'guard_name',
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Create model_has_roles
        |--------------------------------------------------------------------------
        */

        Schema::connection('tenant')->create(
            'model_has_roles',
            function (Blueprint $table) {
                $table->unsignedBigInteger(
                    'role_id'
                );

                $table->string(
                    'model_type'
                );

                $table->unsignedBigInteger(
                    'model_id'
                );

                $table->index([
                    'model_id',
                    'model_type',
                ]);

                $table->primary([
                    'role_id',
                    'model_id',
                    'model_type',
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Required roles
        |--------------------------------------------------------------------------
        */

        DB::connection('tenant')
            ->table('roles')
            ->insert([
                [
                    'name' => 'owner',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'manager',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Clear Spatie permission cache
        |--------------------------------------------------------------------------
        */

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Get real tenant connection
        |--------------------------------------------------------------------------
        */

        $realConnection = DB::connection(
            'tenant'
        );

        /*
        |--------------------------------------------------------------------------
        | Partial connection mock
        |--------------------------------------------------------------------------
        |
        | Real SQLite queries continue to work.
        |
        | transaction() is executed directly.
        |
        | afterCommit() does nothing.
        |--------------------------------------------------------------------------
        */

        $this->tenantConnection = Mockery::mock(
            $realConnection
        )->makePartial();

        $this->tenantConnection
            ->shouldReceive('transaction')
            ->zeroOrMoreTimes()
            ->andReturnUsing(
                function (Closure $callback) {
                    return $callback();
                }
            );

        $this->tenantConnection
            ->shouldReceive('afterCommit')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        /*
        |--------------------------------------------------------------------------
        | Replace DatabaseManager in container
        |--------------------------------------------------------------------------
        */

        $databaseManager = Mockery::mock(
            DatabaseManager::class
        )->makePartial();

        $databaseManager
            ->shouldReceive('connection')
            ->with('tenant')
            ->zeroOrMoreTimes()
            ->andReturn(
                $this->tenantConnection
            );

        DB::clearResolvedInstance(
            'db'
        );

        $this->app->instance(
            'db',
            $databaseManager
        );

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        $this->notify = Mockery::mock(
            TaskNotificationService::class
        );

        $this->notify
            ->shouldReceive(
                'completeTaskNotify'
            )
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive(
                'cancelTaskNotify'
            )
            ->zeroOrMoreTimes()
            ->andReturnNull();

        $this->notify
            ->shouldReceive(
                'onHoldTaskNotify'
            )
            ->zeroOrMoreTimes()
            ->andReturnNull();

        /*
        |--------------------------------------------------------------------------
        | Disable activity logging
        |--------------------------------------------------------------------------
        */

        activity()->disableLogging();

        /*
        |--------------------------------------------------------------------------
        | Real service
        |--------------------------------------------------------------------------
        */

        $this->service = new TaskActionService(
            $this->notify
        );
    }

    protected function tearDown(): void
    {
        activity()->enableLogging();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        DB::clearResolvedInstance(
            'db'
        );

        DB::purge(
            'tenant'
        );

        Mockery::close();

        /*
         * Delete test database.
         */
        if (
            isset($this->databaseFile) &&
            file_exists($this->databaseFile)
        ) {
            unlink(
                $this->databaseFile
            );
        }

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function makeTask(
        TaskStatus $status,
        ?Carbon $dueDate = null
    ): Task {
        $task = new Task();

        $task->status = $status;

        $task->due_date =
            $dueDate ?? now()->addDay();

        $task->project_id = 1;

        $task->team_id = 1;

        return $task;
    }

    private function attachUserToTaskTeam(
        Task $task,
        int $userId
    ): void {
        $user = new \stdClass();

        $user->id = $userId;

        $tenantUser = new TenantUser();

        $tenantUser->setRelation(
            'user',
            $user
        );

        $team = new \stdClass();

        $team->tenantUsers = new Collection([
            $tenantUser,
        ]);

        $task->setRelation(
            'team',
            $team
        );
    }

    private function attachEmptyTeam(
        Task $task
    ): void {
        $team = new \stdClass();

        $team->tenantUsers = new Collection();

        $task->setRelation(
            'team',
            $team
        );
    }

    private function mockUpdate(
        Task $task,
        array $expectedData
    ): Task {
        $task = Mockery::mock(
            $task
        )->makePartial();

        $task
            ->shouldReceive('update')
            ->once()
            ->with(
                $expectedData
            )
            ->andReturnTrue();

        return $task;
    }

    private function mockLoad(
        Task $task
    ): Task {
        $task
            ->shouldReceive('load')
            ->once()
            ->with(
                'project',
                'team.tenantUsers.user'
            )
            ->andReturnSelf();

        return $task;
    }

    /*
    |--------------------------------------------------------------------------
    | completeTask
    |--------------------------------------------------------------------------
    */

    public function test_complete_task_completes_in_progress_task(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS,
            now()->addDay()
        );

        $this->attachUserToTaskTeam(
            $task,
            10
        );

        $task = Mockery::mock($task)->makePartial();

        $task
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (array $data) {
                return isset($data['status'])
                    && $data['status'] === TaskStatus::COMPLETED->value
                    && isset($data['completed_at'])
                    && $data['completed_at'] instanceof Carbon;
            })
            ->andReturnTrue();

        $task
            ->shouldReceive('load')
            ->once()
            ->with(
                'project',
                'team.tenantUsers.user'
            )
            ->andReturnSelf();

        $result = $this->service->completeTask($task);

        $this->assertSame(
            $task,
            $result
        );
    }

    public function test_complete_task_cancels_expired_task_and_throws_exception(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS,
            now()->subDay()
        );

        $this->attachUserToTaskTeam(
            $task,
            10
        );

        $task = Mockery::mock(
            $task
        )->makePartial();

        $task
            ->shouldReceive('update')
            ->once()
            ->with([
                'status' =>
                TaskStatus::CANCELLED->value,
            ])
            ->andReturnTrue();

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'This task has expired and cannot be completed.'
        );

        $this->service->completeTask(
            $task
        );
    }

    public function test_complete_task_throws_exception_when_task_is_not_in_progress(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::OPEN,
            now()->addDay()
        );

        $this->attachUserToTaskTeam(
            $task,
            10
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only tasks in progress can be completed.'
        );

        $this->service->completeTask(
            $task
        );
    }

    public function test_complete_task_throws_exception_when_user_cannot_manage_task(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS,
            now()->addDay()
        );

        $this->attachEmptyTeam(
            $task
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'You cant manage This Task'
        );

        $this->service->completeTask(
            $task
        );
    }

    /*
    |--------------------------------------------------------------------------
    | cancelTask
    |--------------------------------------------------------------------------
    */

    public function test_cancel_task_changes_task_status_to_cancelled(): void
    {
        $task = $this->makeTask(
            TaskStatus::OPEN
        );

        $task = $this->mockUpdate(
            $task,
            [
                'status' =>
                TaskStatus::CANCELLED->value,
            ]
        );

        $task = $this->mockLoad(
            $task
        );

        $result = $this->service
            ->cancelTask($task);

        $this->assertSame(
            $task,
            $result
        );
    }

    public function test_cancel_task_allows_in_progress_task(): void
    {
        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS
        );

        $task = $this->mockUpdate(
            $task,
            [
                'status' =>
                TaskStatus::CANCELLED->value,
            ]
        );

        $task = $this->mockLoad(
            $task
        );

        $result = $this->service
            ->cancelTask($task);

        $this->assertSame(
            $task,
            $result
        );
    }

    public function test_cancel_task_allows_on_hold_task(): void
    {
        $task = $this->makeTask(
            TaskStatus::OnHold
        );

        $task = $this->mockUpdate(
            $task,
            [
                'status' =>
                TaskStatus::CANCELLED->value,
            ]
        );

        $task = $this->mockLoad(
            $task
        );

        $result = $this->service
            ->cancelTask($task);

        $this->assertSame(
            $task,
            $result
        );
    }

    public function test_cancel_task_throws_exception_when_task_is_completed(): void
    {
        $task = $this->makeTask(
            TaskStatus::COMPLETED
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'This task cannot be cancelled.'
        );

        $this->service->cancelTask(
            $task
        );
    }

    public function test_cancel_task_throws_exception_when_task_is_already_cancelled(): void
    {
        $task = $this->makeTask(
            TaskStatus::CANCELLED
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'This task cannot be cancelled.'
        );

        $this->service->cancelTask(
            $task
        );
    }

    /*
    |--------------------------------------------------------------------------
    | onHoldTask
    |--------------------------------------------------------------------------
    */

    public function test_on_hold_task_changes_in_progress_task_to_on_hold(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS
        );

        $this->attachUserToTaskTeam(
            $task,
            10
        );

        $task = $this->mockUpdate(
            $task,
            [
                'status' =>
                TaskStatus::OnHold->value,
            ]
        );

        $task = $this->mockLoad(
            $task
        );

        $result = $this->service
            ->onHoldTask($task);

        $this->assertSame(
            $task,
            $result
        );
    }

    public function test_on_hold_task_throws_exception_when_task_is_not_in_progress(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::OPEN
        );

        $this->attachUserToTaskTeam(
            $task,
            10
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'Only tasks in progress can be put on hold.'
        );

        $this->service->onHoldTask(
            $task
        );
    }

    public function test_on_hold_task_throws_exception_when_user_cannot_manage_task(): void
    {
        Auth::shouldReceive('id')
            ->zeroOrMoreTimes()
            ->andReturn(10);

        $task = $this->makeTask(
            TaskStatus::IN_PROGRESS
        );

        $this->attachEmptyTeam(
            $task
        );

        $this->expectException(
            BusinessRuleException::class
        );

        $this->expectExceptionMessage(
            'You cant manage This Task'
        );

        $this->service->onHoldTask(
            $task
        );
    }
}
