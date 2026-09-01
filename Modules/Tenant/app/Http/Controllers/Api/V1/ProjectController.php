<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use Modules\Tenant\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Services\Project\ProjectService;

class ProjectController extends Controller
{
    use AuthorizesRequests;
    public  function __construct(public ProjectService $projectService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);
        $fiters = $request->only([
            'name',
            'description',
            'status',
            'priority',
            'start_date',
            'end_date',
            'is_active',
        ]);
        $projects = $this->projectService->getAll($fiters);
        return $this->successMessage('Suuccessfully Retrieved Projects', ['projects' => $projects], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);
        $project = $this->projectService->store($request->validated());
        return $this->successMessage('Suuccessfully Created Project', ['project' => $project], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $data = $this->projectService->get($project);
        return $this->successMessage('Suuccessfully Retrieved Project', ['project' => $data], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        $data = $this->projectService->update($request->validated(), $project);
        return $this->successMessage('Suuccessfully Updated Project', ['project' => $data], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $this->projectService->destroy($project);
        return $this->successMessage('Suuccessfully Deleted Project', [], 200);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Project $project): JsonResponse
    {
        $this->authorize('restore', $project);
        $data = $this->projectService->restore($project);
        return $this->successMessage('Suuccessfully Restored Project', ['project' => $data], 200);
    }

    /**
     * Force Remove the specified resource from storage.
     */
    public function forceDelete(Project $project): JsonResponse
    {
        $this->authorize('forceDelete', $project);
        $this->projectService->forceDelete($project);
        return $this->successMessage('Suuccessfully Deleted Project', [], 200);
    }

    /**
     * Get Trashed
     */
    public function getTrashed(Project $project): JsonResponse
    {
        $this->authorize('getTrashed', $project);
        $data = $this->projectService->getTrashed($project);
        return $this->successMessage('Suuccessfully Restored Project', ['project' => $data], 200);
    }

    /**
     * Get All Trashed
     */
    public function getAllTrashed(Request $request): JsonResponse
    {
        $this->authorize('getAllTrashed', Project::class);
        $filters = $request->only(['name', 'description', 'status', 'priority', 'start_date', 'end_date', 'is_active']);
        $data = $this->projectService->getAllTrashed($filters);
        return $this->successMessage('Suuccessfully Restored Project', ['project' => $data], 200);
    }

    /**
     * Restore All
     */
    public function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAll', Project::class);
        $data = $this->projectService->restoreAll();
        return $this->successMessage('Suuccessfully Restored Project', ['project' => $data], 200);
    }

    /**
     * Force Delete All
     */
    public function forceDeleteAll(): JsonResponse
    {
        $this->authorize('forceDeleteAll', Project::class);
        $data = $this->projectService->forceDeleteAll();
        return $this->successMessage('Suuccessfully Restored Project', ['project' => $data], 200);
    }
}
