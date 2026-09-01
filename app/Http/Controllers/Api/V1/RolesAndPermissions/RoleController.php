<?php

namespace App\Http\Controllers\Api\V1\RolesAndPermissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Roles\SyncRolesToUserRequest;
use App\Models\User;
use App\Services\Roles_and_Permissions\RoleService;
use Illuminate\Http\Request;
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
    public function assignRoleToUser(User $user, Role $role)
    {

        $status = $this->roleService->assignRoleToUser($user, $role);

        return $this->successMessage(
            'Role assigned to user successfully',
            ['status' => $status],
            200
        );
    }

    /**
     * Remove a role from a user.
     */
    public function removeRoleFromUser(User $user, Role $role)
    {
        $status = $this->roleService->removeRoleFromUser($user, $role);

        return $this->successMessage(
            'Role removed from user successfully',
            ['status' => $status],
            200
        );
    }

    /**
     * Sync roles to a user.
     */
    public function syncRolesToUser(SyncRolesToUserRequest $request, User $user)
    {
        $roles = $request->validated();

        $status = $this->roleService->syncRolesToUser($user, $roles);

        return $this->successMessage(
            'Roles synced to user successfully',
            ['status' => $status],
            200
        );
    }
}
