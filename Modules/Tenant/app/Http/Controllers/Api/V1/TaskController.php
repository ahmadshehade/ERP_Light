<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use Modules\Tenant\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Services\Task\TaskActionService;
use Modules\Tenant\Services\Task\TaskService;
use Modules\Tenant\Transformers\TaskResource;

class TaskController extends Controller
{

    use AuthorizesRequests;

    public function __construct(public TaskService $taskService, public TaskActionService $taskAction) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);
        $filters = $request->only(['title', 'is_active', 'description', 'team_id', 'project_id']);
        $tasks = $this->taskService->getAll($filters);
        return $this->successMessage('Successfully retrieved tasks', TaskResource::collection($tasks), 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);
        $task = $this->taskService->store($request->validated());
        return $this->successMessage('Successfully created task', TaskResource::make($task), 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);
        $data = $this->taskService->get($task);
        return $this->successMessage('Successfully retrieved task', TaskResource::make($data), 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);
        $task = $this->taskService->update($request->validated(), $task);
        return $this->successMessage('Successfully updated task', TaskResource::make($task), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $this->taskService->destroy($task);
        return $this->successMessage('Successfully deleted task', [], 200);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Task $task): JsonResponse
    {
        $this->authorize('restore', $task);
        $task = $this->taskService->restore($task);
        return $this->successMessage('Successfully restored task', TaskResource::make($task), 200);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete(Task $task): JsonResponse
    {
        $this->authorize('forceDelete', $task);
        $this->taskService->forceDelete($task);
        return $this->successMessage('Successfully deleted task', [], 200);
    }

    /**
     * Display the specified trashed resource.
     */
    public function getTrashed(Task $task): JsonResponse
    {
        $this->authorize('viewTrashed', Task::class);
        $task = $this->taskService->getTrashed($task);
        return $this->successMessage('Successfully retrieved trashed task', TaskResource::make($task), 200);
    }

    /**
     * Display a listing of the trashed resource.
     */
    public function getTrashedTasks(): JsonResponse
    {
        $this->authorize('viewAllTrashed', Task::class);
        $filters = request()->only(['title', 'is_active', 'description', 'team_id', 'project_id']);
        $tasks = $this->taskService->getAllTrashed($filters);
        return $this->successMessage('Successfully retrieved trashed tasks', TaskResource::collection($tasks), 200);
    }

    /**
     * Restore all trashed resource.
     */
    public function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAll', Task::class);
        $this->taskService->restoreAll();
        return $this->successMessage('Successfully restored all trashed tasks', [], 200);
    }

    /**
     * Force delete all trashed resource.
     */
    public function forceDeleteAll(): JsonResponse
    {
        $this->authorize('forceDeleteAll', Task::class);
        $this->taskService->forceDeleteAll();
        return $this->successMessage('Successfully deleted all trashed tasks', [], 200);
    }

    /**
     * Cancel the specified resource from storage.
     */
    public function cancel(Task $task): JsonResponse
    {
        $this->authorize('cancelTask', $task);
        $task = $this->taskAction->cancelTask($task);
        return $this->successMessage('Successfully cancelled task', TaskResource::make($task), 200);
    }

    /**
     * Complete the specified resource from storage.
     */
    public function complete(Task $task): JsonResponse
    {
        $this->authorize('completeTask', $task);
        $task = $this->taskAction->completeTask($task);
        return $this->successMessage('Successfully completed task', TaskResource::make($task), 200);
    }

    /**
     * On hold the specified resource from storage.
     */
    public function onHold(Task $task): JsonResponse
    {
        $this->authorize('onHoldTask', $task);
        $task = $this->taskAction->onHoldTask($task);
        return $this->successMessage('Successfully on hold task', TaskResource::make($task), 200);
    }
}
