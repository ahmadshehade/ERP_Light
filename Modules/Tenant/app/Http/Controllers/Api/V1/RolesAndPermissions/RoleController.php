<?php

namespace Modules\Tenant\Http\Controllers\Api\V1\RolesAndPermissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Services\Roles_And_Permissions\RoleService;
use Illuminate\Http\Request;
use  Modules\Tenant\Http\Requests\Api\V1\Roles\SyncRolesToUserRequest;
use Modules\Tenant\Models\TenantUser;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        public RoleService $roleService
    ) {}

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $data = $request->only(['name']);

        $roles = $this->roleService->getAll($data);

        return $this->successMessage(
            'Roles retrieved successfully',
            ['roles' => $roles],
            200
        );
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $data = $this->roleService->getRole($role);

        return $this->successMessage(
            'Role retrieved successfully',
            ['role' => $data],
            200
        );
    }

    /**
     * Assign a role to a user.
     */
    public function assignRoleToUser(TenantUser $tenantUser, Role $role)
    {

        $status = $this->roleService->assignRoleToUser($tenantUser, $role);

        return $this->successMessage(
            'Role assigned to user successfully',
            ['status' => $status],
            200
        );
    }

    /**
     * Remove a role from a user.
     */
    public function removeRoleFromUser(TenantUser $tenantUser, Role $role)
    {
        $status = $this->roleService->removeRoleFromUser($tenantUser, $role);

        return $this->successMessage(
            'Role removed from user successfully',
            ['status' => $status],
            200
        );
    }

    /**
     * Sync roles to a user.
     */
    public function syncRolesToUser(SyncRolesToUserRequest $request, TenantUser $tenantUser)
    {
        $roles = $request->validated();

        $status = $this->roleService->syncRolesToUser($tenantUser, $roles);

        return $this->successMessage(
            'Roles synced to user successfully',
            ['status' => $status],
            200
        );
    }


    /**
     * Get roles for a specific user
     *  @prama TenantUser $tenantUser
     * @return JsonResponse
     */
    public function getRoleToTenantUser(TenantUser $tenantUser): JsonResponse
    {
        $roles = $this->roleService->getRolesForUser($tenantUser);
        return $this->successMessage(
            'Roles retrieved successfully',
            ['roles' => $roles],
            200
        );
    }
}
