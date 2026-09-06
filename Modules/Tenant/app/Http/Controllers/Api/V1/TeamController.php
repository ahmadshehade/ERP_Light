<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Teams\StoreTeamRequest;
use Modules\Tenant\Http\Requests\Api\V1\Teams\UpdateTeamRequest;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Services\Team\TeamService;
use Modules\Tenant\Transformers\TeamResource;

class TeamController extends Controller
{
    use AuthorizesRequests;

    public function __construct(public TeamService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);
        $fiters = $request->only(['name', 'is_active', 'description']);
        $teams = $this->service->getAll($fiters);
        return $this->successMessage('Suuccessfully Retrieved Teams', TeamResource::collection($teams), 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $this->authorize('create', Team::class);
        $team = $this->service->store($request->validated());
        return  $this->successMessage('Suuccessfully Created Team', TeamResource::make($team), 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);
        $data = $this->service->get($team);
        return  $this->successMessage('Suuccessfully Retrieved Team', TeamResource::make($data), 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $this->authorize('update', $team);
        $data = $this->service->update($request->validated(), $team);
        return  $this->successMessage('Suuccessfully Updated Team', TeamResource::make($data), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);
        $this->service->destroy($team);
        return  $this->successMessage('Suuccessfully Deleted Team', [], 200);
    }
}
