<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Tenant\Services\TenantUser\TenantUserService;
use Modules\Tenant\Http\Requests\Api\V1\TenantUser\StoreTenantUserRequest;
use Modules\Tenant\Http\Requests\Api\V1\TenantUser\UpdateTenantUserRequest;
use Modules\Tenant\Models\TenantUser;

class TenantUserController extends Controller
{
    use AuthorizesRequests;
    public function __construct(public TenantUserService $tenantUserService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TenantUser::class);
        $filters = $request->only(['user_id', 'is_active']);
        $tenantUsers = $this->tenantUserService->getAll($filters);
        return $this->successMessage('Successfully retrieved tenant users', ['tenantUsers' => $tenantUsers], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantUserRequest $request)
    {
        $this->authorize('create', TenantUser::class);
        $data = $this->tenantUserService->store($request->validated());
        return $this->successMessage('Successfully created tenant user', ['tenantUser' => $data], 200);
    }

    /**
     * Show the specified resource.
     */
    public function show(TenantUser $tenantUser)
    {
        $this->authorize('view', $tenantUser);
        $data = $this->tenantUserService->get($tenantUser);
        return $this->successMessage('Successfully retrieved tenant user', ['tenantUser' => $data], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantUserRequest $request, TenantUser $tenantUser)
    {
        $this->authorize('update', $tenantUser);
        $data = $this->tenantUserService->update($request->validated(), $tenantUser);
        return $this->successMessage('Successfully updated tenant user', ['tenantUser' => $data], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TenantUser $tenantUser)
    {
        $this->authorize('delete', $tenantUser);
        $this->tenantUserService->destroy($tenantUser);
        return $this->successMessage('Successfully deleted tenant user', [], 200);
    }
}
