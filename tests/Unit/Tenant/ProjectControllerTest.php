<?php

namespace Tests\Unit\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Mockery;
use Modules\Tenant\Http\Controllers\Api\V1\ProjectController;
use Modules\Tenant\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use Modules\Tenant\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Services\Project\ProjectService;
use Modules\Tenant\Services\Project\ProjectStatusService;
use Modules\Tenant\Transformers\ProjectResource;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function makeController(
        ProjectService $projectService,
        ProjectStatusService $state
    ): ProjectController {
        return Mockery::mock(ProjectController::class, [
            $projectService,
            $state,
        ])->makePartial();
    }

    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_successful_response(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $request = Request::create('/api/v1/projects', 'GET', [
            'name' => 'Project One',
            'description' => 'Test Project',
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'is_active' => true,
            'ignored' => 'value',
        ]);

        $filters = [
            'name' => 'Project One',
            'description' => 'Test Project',
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'is_active' => true,
        ];

        $projects = collect();

        $projectService->shouldReceive('getAll')
            ->once()
            ->with($filters)
            ->andReturn($projects);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('viewAny', Project::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Retrieved Projects',
                Mockery::type(AnonymousResourceCollection::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Retrieved Projects',
                    'data' => [],
                ], 200)
            );

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $request = Mockery::mock(StoreProjectRequest::class);

        $validatedData = [
            'name' => [
                'en' => 'Project One',
                'ar' => 'المشروع الأول',
            ],
            'description' => [
                'en' => 'Project Description',
                'ar' => 'وصف المشروع',
            ],
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'is_active' => true,
        ];

        $project = new Project();
        $project->id = 1;

        $request->shouldReceive('validated')
            ->once()
            ->andReturn($validatedData);

        $projectService->shouldReceive('store')
            ->once()
            ->with($validatedData)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('create', Project::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Created Project',
                Mockery::type(ProjectResource::class),
                201
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Created Project',
                    'data' => [],
                ], 201)
            );

        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | show
    |--------------------------------------------------------------------------
    */

    public function test_show_returns_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $projectService->shouldReceive('get')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('view', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Retrieved Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Retrieved Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->show($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $request = Mockery::mock(UpdateProjectRequest::class);

        $project = new Project();
        $project->id = 1;

        $validatedData = [
            'name' => [
                'en' => 'Updated Project',
                'ar' => 'المشروع المحدث',
            ],
            'description' => [
                'en' => 'Updated Description',
                'ar' => 'الوصف المحدث',
            ],
            'is_active' => true,
        ];

        $request->shouldReceive('validated')
            ->once()
            ->andReturn($validatedData);

        $projectService->shouldReceive('update')
            ->once()
            ->with($validatedData, $project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('update', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Updated Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Updated Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->update($request, $project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | destroy
    |--------------------------------------------------------------------------
    */

    public function test_destroy_deletes_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $projectService->shouldReceive('destroy')
            ->once()
            ->with($project)
            ->andReturn(true);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('delete', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Deleted Project',
                [],
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Deleted Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->destroy($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | restore
    |--------------------------------------------------------------------------
    */

    public function test_restore_restores_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $projectService->shouldReceive('restore')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('restore', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Restored Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Restored Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->restore($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | forceDelete
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_deletes_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $projectService->shouldReceive('forceDelete')
            ->once()
            ->with($project)
            ->andReturn(true);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('forceDelete', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Deleted Project',
                [],
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Deleted Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->forceDelete($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | getTrashed
    |--------------------------------------------------------------------------
    */

    public function test_get_trashed_returns_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $projectService->shouldReceive('getTrashed')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('getTrashed', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Restored Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Restored Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->getTrashed($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | getAllTrashed
    |--------------------------------------------------------------------------
    */

    public function test_get_all_trashed_returns_projects_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $request = Request::create('/api/v1/projects/trashed', 'GET', [
            'name' => 'Project',
            'description' => 'Description',
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'is_active' => true,
            'ignored' => 'value',
        ]);

        $filters = [
            'name' => 'Project',
            'description' => 'Description',
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'is_active' => true,
        ];

        $projects = collect();

        $projectService->shouldReceive('getAllTrashed')
            ->once()
            ->with($filters)
            ->andReturn($projects);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('getAllTrashed', Project::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Restored Project',
                Mockery::type(AnonymousResourceCollection::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Restored Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->getAllTrashed($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | restoreAll
    |--------------------------------------------------------------------------
    */

    public function test_restore_all_restores_projects_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $projectService->shouldReceive('restoreAll')
            ->once()
            ->andReturn(true);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('restoreAll', Project::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Restored Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Restored Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->restoreAll();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | forceDeleteAll
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_all_deletes_projects_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $projectService->shouldReceive('forceDeleteAll')
            ->once()
            ->andReturn(true);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('forceDeleteAll', Project::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Restored Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Restored Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->forceDeleteAll();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | onHold
    |--------------------------------------------------------------------------
    */

    public function test_on_hold_puts_project_on_hold_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $state->shouldReceive('hold')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('onHold', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully On Hold Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully On Hold Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->onHold($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | resume
    |--------------------------------------------------------------------------
    */

    public function test_resume_resumes_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $state->shouldReceive('resume')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('resume', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Resume Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Resume Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->resume($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | cancel
    |--------------------------------------------------------------------------
    */

    public function test_cancel_cancels_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $state->shouldReceive('cancel')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('cancel', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Cancel Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Cancel Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->cancel($project);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /*
    |--------------------------------------------------------------------------
    | complete
    |--------------------------------------------------------------------------
    */

    public function test_complete_completes_project_successfully(): void
    {
        $projectService = Mockery::mock(ProjectService::class);
        $state = Mockery::mock(ProjectStatusService::class);

        $project = new Project();
        $project->id = 1;

        $state->shouldReceive('complete')
            ->once()
            ->with($project)
            ->andReturn($project);

        $controller = $this->makeController($projectService, $state);

        $controller->shouldReceive('authorize')
            ->once()
            ->with('complete', $project)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Complete Project',
                Mockery::type(ProjectResource::class),
                200
            )
            ->andReturn(
                response()->json([
                    'message' => 'Suuccessfully Complete Project',
                    'data' => [],
                ], 200)
            );

        $response = $controller->complete($project);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
