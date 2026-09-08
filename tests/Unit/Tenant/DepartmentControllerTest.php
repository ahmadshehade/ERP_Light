<?php

namespace Tests\Unit\Tenant;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Modules\Tenant\Http\Controllers\Api\V1\DepartmentController;
use Modules\Tenant\Http\Requests\Api\V1\Department\StoreDepartmentRequest;
use Modules\Tenant\Http\Requests\Api\V1\Department\UpdateDepartmentRequest;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Services\Department\DepartmentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    private DepartmentService $departmentService;

    private TestDepartmentController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentService = Mockery::mock(
            DepartmentService::class
        );

        $this->controller = new TestDepartmentController(
            $this->departmentService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Create Department
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_creates_department_successfully(): void
    {
        $department = new Department();

        $department->id = 1;

        $department->name = [
            'en' => 'IT',
            'ar' => 'تقنية المعلومات',
        ];

        $department->description = [
            'en' => 'IT Department',
            'ar' => 'قسم تقنية المعلومات',
        ];

        $department->is_active = true;

        $request = Mockery::mock(
            StoreDepartmentRequest::class
        );

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn([
                'name' => [
                    'en' => 'IT',
                    'ar' => 'تقنية المعلومات',
                ],
                'description' => [
                    'en' => 'IT Department',
                    'ar' => 'قسم تقنية المعلومات',
                ],
                'is_active' => true,
            ]);

        $this->departmentService
            ->shouldReceive('store')
            ->once()
            ->andReturn($department);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(
            JsonResponse::class,
            $response
        );

        $this->assertEquals(
            201,
            $response->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Update Department
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_updates_department_successfully(): void
    {
        $department = new Department();

        $department->id = 1;

        $department->name = [
            'en' => 'Human Resources',
            'ar' => 'الموارد البشرية',
        ];

        $department->description = [
            'en' => 'HR Department',
            'ar' => 'قسم الموارد البشرية',
        ];

        $department->is_active = true;

        $request = Mockery::mock(
            UpdateDepartmentRequest::class
        );

        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn([
                'name' => [
                    'en' => 'Human Resources',
                    'ar' => 'الموارد البشرية',
                ],
                'description' => [
                    'en' => 'HR Department',
                    'ar' => 'قسم الموارد البشرية',
                ],
                'is_active' => true,
            ]);

        $this->departmentService
            ->shouldReceive('update')
            ->once()
            ->with(
                [
                    'name' => [
                        'en' => 'Human Resources',
                        'ar' => 'الموارد البشرية',
                    ],
                    'description' => [
                        'en' => 'HR Department',
                        'ar' => 'قسم الموارد البشرية',
                    ],
                    'is_active' => true,
                ],
                $department
            )
            ->andReturn($department);

        $response = $this->controller->update(
            $request,
            $department
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
    | 3. Soft Delete + Restore
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_soft_deletes_and_restores_department(): void
    {
        $department = new Department();

        $department->id = 1;

        /*
         * Soft Delete
         */

        $this->departmentService
            ->shouldReceive('destroy')
            ->once()
            ->with($department)
            ->andReturn(true);

        $deleteResponse = $this->controller->destroy(
            $department
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $deleteResponse
        );

        $this->assertEquals(
            200,
            $deleteResponse->getStatusCode()
        );

        /*
         * Restore
         */

        $this->departmentService
            ->shouldReceive('restore')
            ->once()
            ->with($department)
            ->andReturn($department);

        $restoreResponse = $this->controller->restore(
            $department
        );

        $this->assertInstanceOf(
            JsonResponse::class,
            $restoreResponse
        );

        $this->assertEquals(
            200,
            $restoreResponse->getStatusCode()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Permission
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_rejects_action_without_permission(): void
    {
        $controller = new UnauthorizedDepartmentController(
            $this->departmentService
        );

        $department = new Department();

        $department->id = 1;

        $this->expectException(
            \Illuminate\Auth\Access\AuthorizationException::class
        );

        $controller->destroy(
            $department
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Validation
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_rejects_invalid_department_data(): void
    {
        $request = Mockery::mock(
            StoreDepartmentRequest::class
        );

        $request
            ->shouldReceive('validated')
            ->once()
            ->andThrow(
                new \Illuminate\Validation\ValidationException(
                    validator(
                        [],
                        [
                            'name' => [
                                'required',
                                'array',
                            ],
                        ]
                    )
                )
            );

        $this->expectException(
            \Illuminate\Validation\ValidationException::class
        );

        $this->controller->store($request);
    }
}


/*
|--------------------------------------------------------------------------
| Test Controller
|--------------------------------------------------------------------------
|
| This controller bypasses authorization.
| We use it for testing the Controller itself.
|
*/

class TestDepartmentController extends DepartmentController
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
| Used only for permission test.
|
*/

class UnauthorizedDepartmentController extends DepartmentController
{
    public function authorize(
        $ability,
        $arguments = []
    ) {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'This action is unauthorized.'
        );
    }
}
