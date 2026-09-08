<?php

namespace Tests\Unit\Modules\Tenant\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Mockery;
use Modules\Tenant\Http\Controllers\Api\V1\TeamController;
use Modules\Tenant\Http\Requests\Api\V1\Teams\StoreTeamRequest;
use Modules\Tenant\Http\Requests\Api\V1\Teams\UpdateTeamRequest;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Services\Team\TeamService;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_index_returns_successful_response(): void
    {
        $service = Mockery::mock(TeamService::class);

        $request = Request::create('/api/v1/teams', 'GET', [
            'name' => 'Development',
            'is_active' => true,
            'description' => 'Development Team',
        ]);

        $teams = collect();

        $service->shouldReceive('getAll')
            ->once()
            ->with([
                'name' => 'Development',
                'is_active' => true,
                'description' => 'Development Team',
            ])
            ->andReturn($teams);

        $controller = Mockery::mock(TeamController::class, [$service])
            ->makePartial();

        $controller->shouldReceive('authorize')
            ->once()
            ->with('viewAny', Team::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Retrieved Teams',
                Mockery::type(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class),
                200
            )
            ->andReturn(response()->json([
                'message' => 'Suuccessfully Retrieved Teams',
                'data' => [],
            ], 200));

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_store_creates_team_successfully(): void
    {
        $service = Mockery::mock(TeamService::class);

        $request = Mockery::mock(StoreTeamRequest::class);

        $validatedData = [
            'name' => [
                'en' => 'Development',
                'ar' => 'التطوير',
            ],
            'description' => [
                'en' => 'Development Team',
                'ar' => 'فريق التطوير',
            ],
            'is_active' => true,
        ];

        $team = new Team();
        $team->id = 1;

        $request->shouldReceive('validated')
            ->once()
            ->andReturn($validatedData);

        $service->shouldReceive('store')
            ->once()
            ->with($validatedData)
            ->andReturn($team);

        $controller = Mockery::mock(TeamController::class, [$service])
            ->makePartial();

        $controller->shouldReceive('authorize')
            ->once()
            ->with('create', Team::class)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Created Team',
                Mockery::type(\Modules\Tenant\Transformers\TeamResource::class),
                201
            )
            ->andReturn(response()->json([
                'message' => 'Suuccessfully Created Team',
                'data' => [],
            ], 201));

        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_show_returns_team_successfully(): void
    {
        $service = Mockery::mock(TeamService::class);

        $team = new Team();
        $team->id = 1;

        $service->shouldReceive('get')
            ->once()
            ->with($team)
            ->andReturn($team);

        $controller = Mockery::mock(TeamController::class, [$service])
            ->makePartial();

        $controller->shouldReceive('authorize')
            ->once()
            ->with('view', $team)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Retrieved Team',
                Mockery::type(\Modules\Tenant\Transformers\TeamResource::class),
                200
            )
            ->andReturn(response()->json([
                'message' => 'Suuccessfully Retrieved Team',
                'data' => [],
            ], 200));

        $response = $controller->show($team);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_updates_team_successfully(): void
    {
        $service = Mockery::mock(TeamService::class);

        $request = Mockery::mock(UpdateTeamRequest::class);

        $team = new Team();
        $team->id = 1;

        $validatedData = [
            'name' => [
                'en' => 'Updated Development',
                'ar' => 'التطوير المحدث',
            ],
            'description' => [
                'en' => 'Updated Development Team',
                'ar' => 'فريق التطوير المحدث',
            ],
            'is_active' => true,
        ];

        $request->shouldReceive('validated')
            ->once()
            ->andReturn($validatedData);

        $service->shouldReceive('update')
            ->once()
            ->with($validatedData, $team)
            ->andReturn($team);

        $controller = Mockery::mock(TeamController::class, [$service])
            ->makePartial();

        $controller->shouldReceive('authorize')
            ->once()
            ->with('update', $team)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Updated Team',
                Mockery::type(\Modules\Tenant\Transformers\TeamResource::class),
                200
            )
            ->andReturn(response()->json([
                'message' => 'Suuccessfully Updated Team',
                'data' => [],
            ], 200));

        $response = $controller->update($request, $team);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_destroy_deletes_team_successfully(): void
    {
        $service = Mockery::mock(TeamService::class);

        $team = new Team();
        $team->id = 1;

        $service->shouldReceive('destroy')
            ->once()
            ->with($team)
            ->andReturn(true);

        $controller = Mockery::mock(TeamController::class, [$service])
            ->makePartial();

        $controller->shouldReceive('authorize')
            ->once()
            ->with('delete', $team)
            ->andReturn(true);

        $controller->shouldReceive('successMessage')
            ->once()
            ->with(
                'Suuccessfully Deleted Team',
                [],
                200
            )
            ->andReturn(response()->json([
                'message' => 'Suuccessfully Deleted Team',
                'data' => [],
            ], 200));

        $response = $controller->destroy($team);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
