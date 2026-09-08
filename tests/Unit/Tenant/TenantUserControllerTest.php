<?php

namespace Tests\Unit\Tenant;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Modules\Tenant\Http\Controllers\Api\V1\TenantUserController;
use Modules\Tenant\Http\Requests\Api\V1\TenantUser\StoreTenantUserRequest;
use Modules\Tenant\Http\Requests\Api\V1\TenantUser\UpdateTenantUserRequest;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Services\TenantUser\TenantUserService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantUserControllerTest extends TestCase
{
    private TenantUserService $tenantUserService;

    private TestTenantUserController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantUserService = Mockery::mock(
            TenantUserService::class
        );

        $this->controller = new TestTenantUserController(
            $this->tenantUserService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Index
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_retrieves_tenant_users_successfully(): void
    {
        $request = Request::create(
            '/api/tenant-users',
            'GET'
        );

        $this->tenantUserService
            ->shouldReceive('getAll')
            ->once()
            ->with([])
            ->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    15,
                    1
                )
            );

        $response = $this->controller->index($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Store
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_creates_tenant_user_successfully(): void
    {
        $tenantUser = new TenantUser();

        $tenantUser->id = 1;
        $tenantUser->user_id = 10;
        $tenantUser->is_active = true;

        $request = Mockery::mock(
            StoreTenantUserRequest::class
        );

        $data = [
            'user_id' => 10,
            'is_active' => true,
            'department_ids' => [1, 2],
            'position_ids' => [1],
            'team_ids' => [1],
        ];

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($data);

        $this->tenantUserService
            ->shouldReceive('store')
            ->once()
            ->with($data)
            ->andReturn($tenantUser);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Show
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_retrieves_tenant_user_successfully(): void
    {
        $tenantUser = new TenantUser();

        $tenantUser->id = 1;
        $tenantUser->user_id = 10;
        $tenantUser->is_active = true;

        $this->tenantUserService
            ->shouldReceive('get')
            ->once()
            ->with($tenantUser)
            ->andReturn($tenantUser);

        $response = $this->controller->show(
            $tenantUser
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Update
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_updates_tenant_user_successfully(): void
    {
        $tenantUser = new TenantUser();

        $tenantUser->id = 1;
        $tenantUser->user_id = 10;
        $tenantUser->is_active = true;

        $request = Mockery::mock(
            UpdateTenantUserRequest::class
        );

        $data = [
            'is_active' => false,
            'department_ids' => [1],
            'position_ids' => [1],
            'team_ids' => [1],
        ];

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($data);

        $this->tenantUserService
            ->shouldReceive('update')
            ->once()
            ->with(
                $data,
                $tenantUser
            )
            ->andReturn($tenantUser);

        $response = $this->controller->update(
            $request,
            $tenantUser
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Destroy
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_deletes_tenant_user_successfully(): void
    {
        $tenantUser = new TenantUser();

        $tenantUser->id = 1;
        $tenantUser->user_id = 10;
        $tenantUser->is_active = true;

        $this->tenantUserService
            ->shouldReceive('destroy')
            ->once()
            ->with($tenantUser)
            ->andReturn(true);

        $response = $this->controller->destroy(
            $tenantUser
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Authorization
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_rejects_action_without_permission(): void
    {
        $controller = new UnauthorizedTenantUserController(
            $this->tenantUserService
        );

        $tenantUser = new TenantUser();

        $tenantUser->id = 1;
        $tenantUser->user_id = 10;

        $this->expectException(
            AuthorizationException::class
        );

        $controller->destroy($tenantUser);
    }
}


/*
|--------------------------------------------------------------------------
| Test Controller
|--------------------------------------------------------------------------
|
| Bypasses authorization so we can test the controller independently.
|
*/

class TestTenantUserController extends TenantUserController
{
    public function authorize(
        $ability,
        $arguments = []
    ) {
        return true;
    }
}


/*
|--------------------------------------------------------------------------
| Unauthorized Controller
|--------------------------------------------------------------------------
|
| Used only to verify authorization rejection.
|
*/

class UnauthorizedTenantUserController extends TenantUserController
{
    public function authorize(
        $ability,
        $arguments = []
    ) {
        throw new AuthorizationException(
            'This action is unauthorized.'
        );
    }
}
