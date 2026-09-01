<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Positions\StorePositionRequest;
use Modules\Tenant\Http\Requests\Api\V1\Positions\UpdatePositionRequest;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Services\Position\PositionService;

class PositionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(public PositionService $positionSevice) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);
        $filters = $request->only(['name', 'description']);
        $positiions = $this->positionSevice->getAll($filters);
        return $this->successMessage('Successfully fetched positions', ['positions' => $positiions], 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request): jsonResponse
    {
        $this->authorize('create', Position::class);
        $position = $this->positionSevice->store($request->validated());
        return $this->successMessage('Successfully created position', ['position' => $position], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Position $position): jsonResponse
    {
        $this->authorize('view', $position);
        $data = $this->positionSevice->get($position);
        return $this->successMessage('Successfully fetched position', ['position' => $data], 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): jsonResponse
    {
        $this->authorize('update', $position);
        $data = $this->positionSevice->update($position, $request->validated());
        return $this->successMessage('Successfully updated position', ['position' => $data], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): jsonResponse
    {
        $this->authorize('delete', $position);
        $success = $this->positionSevice->destroy($position);
        return $this->successMessage('Successfully deleted position', ['success' => $success], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function restore(Position $position): jsonResponse
    {
        $this->authorize('restore', $position);
        $success = $this->positionSevice->restore($position);
        return $this->successMessage('Successfully restored position', ['success' => $success], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function forceDelete(Position $position): jsonResponse
    {
        $this->authorize('forceDelete', $position);
        $success = $this->positionSevice->forceDelete($position);
        return $this->successMessage('Successfully deleted position', ['success' => $success], 200);
    }

    /**
     *  Get Trashed Position
     */
    public function getTrashed(Position $position): jsonResponse
    {
        $this->authorize('getTrashed', $position);
        $position = $this->positionSevice->getTrashedPositon($position);
        return $this->successMessage('Successfully fetched trashed position', ['position' => $position], 200);
    }

    /**
     * Get All Trashed Position
     */
    public function getAllTrashed(): jsonResponse
    {
        $this->authorize('getAllTrashed', Position::class);
        $positions = $this->positionSevice->getAllTrashed();
        return $this->successMessage('Successfully fetched trashed positions', ['positions' => $positions], 200);
    }

    /**
     * Restore All Trashed Position
     */
    public function restoreAll(): jsonResponse
    {
        $this->authorize('restoreAll', Position::class);
        $success = $this->positionSevice->restoreAll();
        return $this->successMessage('Successfully restored positions', ['success' => $success], 200);
    }

    /**
     * Force Delete All Trashed Position
     */
    public function forceDeleteAll(): jsonResponse
    {
        $this->authorize('forceDeleteAll', Position::class);
        $success = $this->positionSevice->forceDeleteAll();
        return $this->successMessage('Successfully deleted positions', ['success' => $success], 200);
    }
}
