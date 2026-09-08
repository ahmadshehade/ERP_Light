<?php

namespace Tests\Unit\Tenant;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Tenant\Http\Controllers\Api\V1\PositionController;
use Modules\Tenant\Http\Requests\Api\V1\Positions\StorePositionRequest;
use Modules\Tenant\Http\Requests\Api\V1\Positions\UpdatePositionRequest;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Services\Position\PositionService;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    protected PositionService $service;

    protected PositionController $controller;


    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Mock the PositionService.
         *
         * The service is tested separately.
         * Therefore the controller unit test must not execute
         * real service logic or access the tenant database.
         */
        $this->service = Mockery::mock(PositionService::class);

        /*
         * Partial mock of PositionController.
         *
         * Real controller methods will execute.
         */
        $this->controller = Mockery::mock(
            PositionController::class,
            [$this->service]
        )->makePartial();

        /*
         * Authorization is tested separately in PositionPolicyTest.
         *
         * Therefore authorize() is bypassed here.
         */
        $this->controller
            ->shouldReceive('authorize')
            ->zeroOrMoreTimes()
            ->andReturnTrue();
    }


    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }


    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    protected function mockPosition(): Position
    {
        return Mockery::mock(Position::class);
    }


    protected function successResponse(
        string $message,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }


    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_success_response(): void
    {
        $positions = new LengthAwarePaginator(
            collect(),
            0,
            15,
            1
        );

        $this->service
            ->shouldReceive('getAll')
            ->once()
            ->with([])
            ->andReturn($positions);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully fetched positions',
                ['positions' => $positions],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully fetched positions',
                    200
                )
            );

        $request = Request::create(
            '/api/v1/positions',
            'GET'
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

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully fetched positions',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    */

    public function test_store_returns_created_response(): void
    {
        /*
         * Mock Position instead of Position::make().
         *
         * This prevents Eloquent serialization and tenant DB access.
         */
        $position = $this->mockPosition();

        $data = [
            'name' => [
                'en' => 'Developer',
                'ar' => 'مطور',
            ],
            'description' => [
                'en' => 'Developer position',
                'ar' => 'منصب مطور',
            ],
            'is_active' => true,
        ];

        $request = Mockery::mock(
            StorePositionRequest::class
        );

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($data);

        $this->service
            ->shouldReceive('store')
            ->once()
            ->with($data)
            ->andReturn($position);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully created position',
                ['position' => $position],
                201
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully created position',
                    201
                )
            );

        $response = $this->controller->store($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            201,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully created position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | show
    |--------------------------------------------------------------------------
    */

    public function test_show_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $this->service
            ->shouldReceive('get')
            ->once()
            ->with($position)
            ->andReturn($position);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully fetched position',
                ['position' => $position],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully fetched position',
                    200
                )
            );

        $response = $this->controller->show($position);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully fetched position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $updatedPosition = $this->mockPosition();

        $data = [
            'name' => [
                'en' => 'Senior Developer',
                'ar' => 'مطور أول',
            ],
            'description' => [
                'en' => 'Senior Developer position',
                'ar' => 'منصب مطور أول',
            ],
            'is_active' => true,
        ];

        $request = Mockery::mock(
            UpdatePositionRequest::class
        );

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($data);

        $this->service
            ->shouldReceive('update')
            ->once()
            ->with($position, $data)
            ->andReturn($updatedPosition);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully updated position',
                ['position' => $updatedPosition],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully updated position',
                    200
                )
            );

        $response = $this->controller->update(
            $request,
            $position
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully updated position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | destroy
    |--------------------------------------------------------------------------
    */

    public function test_destroy_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $this->service
            ->shouldReceive('destroy')
            ->once()
            ->with($position)
            ->andReturn(true);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully deleted position',
                ['success' => true],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully deleted position',
                    200
                )
            );

        $response = $this->controller->destroy($position);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully deleted position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | restore
    |--------------------------------------------------------------------------
    */

    public function test_restore_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $this->service
            ->shouldReceive('restore')
            ->once()
            ->with($position)
            ->andReturn($position);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully restored position',
                ['success' => $position],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully restored position',
                    200
                )
            );

        $response = $this->controller->restore($position);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully restored position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | forceDelete
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $this->service
            ->shouldReceive('forceDelete')
            ->once()
            ->with($position)
            ->andReturn(true);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully deleted position',
                ['success' => true],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully deleted position',
                    200
                )
            );

        $response = $this->controller->forceDelete($position);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully deleted position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | getTrashed
    |--------------------------------------------------------------------------
    */

    public function test_get_trashed_returns_success_response(): void
    {
        $position = $this->mockPosition();

        $this->service
            ->shouldReceive('getTrashedPositon')
            ->once()
            ->with($position)
            ->andReturn($position);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully fetched trashed position',
                ['position' => $position],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully fetched trashed position',
                    200
                )
            );

        $response = $this->controller->getTrashed($position);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully fetched trashed position',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | getAllTrashed
    |--------------------------------------------------------------------------
    */

    public function test_get_all_trashed_returns_success_response(): void
    {
        $positions = new LengthAwarePaginator(
            collect(),
            0,
            15,
            1
        );

        $this->service
            ->shouldReceive('getAllTrashed')
            ->once()
            ->andReturn($positions);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully fetched trashed positions',
                ['positions' => $positions],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully fetched trashed positions',
                    200
                )
            );

        $response = $this->controller->getAllTrashed();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully fetched trashed positions',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | restoreAll
    |--------------------------------------------------------------------------
    */

    public function test_restore_all_returns_success_response(): void
    {
        $this->service
            ->shouldReceive('restoreAll')
            ->once()
            ->andReturn(true);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully restored positions',
                ['success' => true],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully restored positions',
                    200
                )
            );

        $response = $this->controller->restoreAll();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully restored positions',
            $data['message']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | forceDeleteAll
    |--------------------------------------------------------------------------
    */

    public function test_force_delete_all_returns_success_response(): void
    {
        $this->service
            ->shouldReceive('forceDeleteAll')
            ->once()
            ->andReturn(true);

        $this->controller
            ->shouldReceive('successMessage')
            ->once()
            ->with(
                'Successfully deleted positions',
                ['success' => true],
                200
            )
            ->andReturn(
                $this->successResponse(
                    'Successfully deleted positions',
                    200
                )
            );

        $response = $this->controller->forceDeleteAll();

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $data = $response->getData(true);

        $this->assertTrue($data['success']);

        $this->assertSame(
            'Successfully deleted positions',
            $data['message']
        );
    }
}
