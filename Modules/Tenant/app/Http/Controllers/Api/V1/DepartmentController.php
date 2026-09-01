<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Department\StoreDepartmentRequest;
use Modules\Tenant\Http\Requests\Api\V1\Department\UpdateDepartmentRequest;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Services\Department\DepartmentService;

class DepartmentController extends Controller
{
    use AuthorizesRequests;
    public function __construct(public DepartmentService $departmentService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);
        $filters = $request->only(['name', 'description', 'is_active']);
        $departments = $this->departmentService->getAll($filters);
        return $this->successMessage('List of Departments', ['departments' => $departments], 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('create', Department::class);
        $department = $this->departmentService->store($request->validated());
        return $this->successMessage('Department created successfully', ['department' => $department], 200);
    }

    /**
     * Show the specified resource.
     */
    public function show(Department  $department)
    {
        $this->authorize('view', $department);
        $data = $this->departmentService->get($department->id);
        return $this->successMessage('Department', ['department' => $data], 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);
        $data = $this->departmentService->update($request->validated(), $department);
        return $this->successMessage('Department updated successfully', ['department' => $data], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);
        $this->departmentService->destroy($department);
        return $this->successMessage('Department deleted successfully', [], 200);
    }

    /**
     *  Restore the specified resource from storage.
     * @prama Department $department
     * @return JsonResponse
     */
    public function restore(Department $department): JsonResponse
    {
        $this->authorize('restore', $department);
        $this->departmentService->restore($department);
        return $this->successMessage('Department restored successfully', [], 200);
    }

    /**
     * forece delete Department
     * @param Department $department
     * @return JsonResponse
     */
    public function forceDelete(Department $department): jsonResponse
    {
        $this->authorize('forceDelete', $department);
        $this->departmentService->forceDelete($department);
        return $this->successMessage('Department deleted permanently', [], 200);
    }

    /**
     * Get trashed Department
     * @param Department $department
     * @return JsonResponse
     */
    public function getTrashed(Department $department): JsonResponse
    {
        $this->authorize('getTrashed', $department);
        $data = $this->departmentService->getTrashed($department);
        return $this->successMessage('Trashed Department', ['department' => $data], 200);
    }

    /**
     * Get all trashed Departments
     * @prama Request $request
     * @return JsonResponse
     *
     */
    public function getAllTrashed(Request $request): JsonResponse
    {
        $this->authorize('getAllTrashed', Department::class);
        $filters = $request->only(['name', 'description', 'is_active']);
        $trashedDepartments = $this->departmentService->getAllTrashed($filters);
        return $this->successMessage('List of Trashed Departments', ['trashedDepartments' => $trashedDepartments], 200);
    }

    /**
     * Force delete all Departments
     * @return JsonResponse
     */
    public function  forceDeleteAll(): JsonResponse
    {
        $this->authorize('forceDeleteAll', Department::class);
        $this->departmentService->forceDeleteAll();
        return $this->successMessage('All Departments deleted permanently', [], 200);
    }

    /**
     * Restore all trashed Departments
     * @return JsonResponse
     *
     */
    public function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAll', Department::class);
        $this->departmentService->restoreAll();
        return $this->successMessage('All Departments restored successfully', [], 200);
    }
}
