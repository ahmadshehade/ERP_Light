<?php

namespace Tests\Unit\Modules\Tenant\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Tenant\Http\Controllers\Api\V1\TaskController;
use Modules\Tenant\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use Modules\Tenant\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Services\Task\TaskActionService;
use Modules\Tenant\Services\Task\TaskService;
use Mockery;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    private TaskService $taskService;

    private TaskActionService $taskAction;

    private TaskController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskService = Mockery::mock(
            TaskService::class
        );

        $this->taskAction = Mockery::mock(
            TaskActionService::class
        );

        /*
         * We create a real TaskController through an anonymous
         * test subclass.
         *
         * This allows us to override authorize() and successMessage()
         * without touching the real controller.
         */
        $this->controller = new class(
            $this->taskService,
            $this->taskAction
        ) extends TaskController {

            public function authorize(
                $ability,
                $arguments = []
            ): mixed {
                return true;
            }

            public function successMessage(
                string $message,
                mixed $data = [],
                int $status = 200
            ): JsonResponse {
                return response()->json([
                    'message' => $message,
                    'data' => $data,
                ], $status);
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            15,
            1,
            [
                'path' => '/api/v1/tasks',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function test_index_calls_service_and_returns_200(): void
    {
        $request = Request::create(
            '/api/v1/tasks',
            'GET'
        );

        $this->taskService
            ->shouldReceive('getAll')
            ->once()
            ->with([])
            ->andReturn(
                $this->emptyPaginator()
            );

        $response = $this->controller->index($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully retrieved tasks',
            $response->getData(true)['message']
        );
    }

    public function test_index_passes_allowed_filters_to_service(): void
    {
        $request = Request::create(
            '/api/v1/tasks',
            'GET',
            [
                'title' => 'test',
                'is_active' => true,
                'description' => 'description',
                'team_id' => 5,
                'project_id' => 10,
                'ignored' => 'must_not_be_passed',
            ]
        );

        $expectedFilters = [
            'title' => 'test',
            'is_active' => true,
            'description' => 'description',
            'team_id' => 5,
            'project_id' => 10,
        ];

        $this->taskService
            ->shouldReceive('getAll')
            ->once()
            ->with($expectedFilters)
            ->andReturn(
                $this->emptyPaginator()
            );

        $response = $this->controller->index($request);

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function test_store_calls_service_and_returns_201(): void
    {
        $request = Mockery::mock(
            StoreTaskRequest::class
        );

        $validated = [
            'project_id' => 1,
            'team_id' => 2,
            'title' => [
                'en' => 'Test task',
                'ar' => 'مهمة اختبار',
            ],
            'description' => [
                'en' => 'Description',
                'ar' => 'الوصف',
            ],
        ];

        $task = new Task();

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($validated);

        $this->taskService
            ->shouldReceive('store')
            ->once()
            ->with($validated)
            ->andReturn($task);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            201,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully created task',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function test_show_calls_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskService
            ->shouldReceive('get')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->show($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function test_update_calls_service_and_returns_200(): void
    {
        $request = Mockery::mock(
            UpdateTaskRequest::class
        );

        $task = new Task();

        $validated = [
            'title' => [
                'en' => 'Updated task',
                'ar' => 'مهمة محدثة',
            ],
        ];

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($validated);

        $this->taskService
            ->shouldReceive('update')
            ->once()
            ->with($validated, $task)
            ->andReturn($task);

        $response = $this->controller->update(
            $request,
            $task
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function test_destroy_calls_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskService
            ->shouldReceive('destroy')
            ->once()
            ->with($task)
            ->andReturnTrue();

        $response = $this->controller->destroy($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully deleted task',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE
    |--------------------------------------------------------------------------
    */

    public function test_restore_calls_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskService
            ->shouldReceive('restore')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->restore($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORCE DELETE
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_calls_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskService
            ->shouldReceive('forceDelete')
            ->once()
            ->with($task)
            ->andReturnTrue();

        $response = $this->controller->forceDelete($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET TRASHED
    |--------------------------------------------------------------------------
    */

    public function test_get_trashed_calls_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskService
            ->shouldReceive('getTrashed')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->getTrashed($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL TRASHED
    |--------------------------------------------------------------------------
    */

    public function test_get_trashed_tasks_calls_service_and_returns_200(): void
    {
        $responsePaginator = $this->emptyPaginator();

        $this->taskService
            ->shouldReceive('getAllTrashed')
            ->once()
            ->with([])
            ->andReturn($responsePaginator);

        $response = $this->controller->getTrashedTasks();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully retrieved trashed tasks',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE ALL
    |--------------------------------------------------------------------------
    */

    public function test_restore_all_calls_service_and_returns_200(): void
    {
        $this->taskService
            ->shouldReceive('restoreAll')
            ->once()
            ->andReturnTrue();

        $response = $this->controller->restoreAll();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully restored all trashed tasks',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORCE DELETE ALL
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_all_calls_service_and_returns_200(): void
    {
        $this->taskService
            ->shouldReceive('forceDeleteAll')
            ->once()
            ->andReturnTrue();

        $response = $this->controller->forceDeleteAll();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully deleted all trashed tasks',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function test_cancel_calls_action_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskAction
            ->shouldReceive('cancelTask')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->cancel($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully cancelled task',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE
    |--------------------------------------------------------------------------
    */

    public function test_complete_calls_action_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskAction
            ->shouldReceive('completeTask')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->complete($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully completed task',
            $response->getData(true)['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ON HOLD
    |--------------------------------------------------------------------------
    */

    public function test_on_hold_calls_action_service_and_returns_200(): void
    {
        $task = new Task();

        $this->taskAction
            ->shouldReceive('onHoldTask')
            ->once()
            ->with($task)
            ->andReturn($task);

        $response = $this->controller->onHold($task);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Successfully on hold task',
            $response->getData(true)['message']
        );
    }
}
