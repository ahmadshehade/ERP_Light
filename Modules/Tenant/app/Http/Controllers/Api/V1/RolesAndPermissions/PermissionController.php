<?php

namespace Modules\Tenant\Http\Controllers\Api\V1\RolesAndPermissions;

use App\Http\Controllers\Controller;
use Modules\Tenant\Services\Roles_And_Permissions\PermissionService;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\Api\V1\Permissions\SyncRolesRequest;
use Modules\Tenant\Models\TenantUser;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    public PermissionService $permissionService;

    /**
     * Create a new controller instance.
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all permissions
     */
    public function index(Request $request)
    {
        $data = $request->only(['name']);
        $permissions = $this->permissionService->getAllPermissions($data);
        return $this->successMessage('Permissions retrieved successfully', ['permissions' => $permissions], 200);
    }

    /**
     * Show a specific permission
     */
    public function show(Permission $permission)
    {
        $permission = $this->permissionService->getPermission($permission);
        return $this->successMessage('Permission retrieved successfully', ['permission' => $permission], 200);
    }


    /**
     * Assign permission to user
     */
    public function syncRolesToPermission(Permission $permission, SyncRolesRequest $request)
    {
        $roles = $request->validated();
        $status = $this->permissionService->syncRoles($permission, $roles);
        return $this->successMessage('Roles synced to permission successfully', ['status' => $status], 200);
    }


    /**
     * Assign permission to user
     */
    public  function givePermissionToUser(TenantUser $tenantUser, Permission $permission)
    {
        $status = $this->permissionService->giveToUser($permission, $tenantUser);
        return $this->successMessage('Permission assigned to user successfully', ['status' => $status], 200);
    }


    /**
     * Remove permission from user
     */
    public  function removePermissionFromUser(TenantUser $tenantUser, Permission $permission)
    {
        $status = $this->permissionService->revokeFromUser($permission, $tenantUser);
        return $this->successMessage('Permission removed from user successfully', ['status' => $status], 200);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(TenantUser $tenantUser)
    {
        $permissions = $this->permissionService->getUserPermissions($tenantUser);
        return $this->successMessage('User permissions retrieved successfully', ['permissions' => $permissions], 200);
    }
}
