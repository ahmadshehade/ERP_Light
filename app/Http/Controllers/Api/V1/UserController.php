<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{

    use  AuthorizesRequests;
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only([
            'name',
            'email',

        ]);

        $users = $this->userService->getAllUsers($filters);

        return $this->successMessage(
            'Users retrieved successfully.',
            ['users' => $users],
            200
        );
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);
        return $this->successMessage(
            'User retrieved successfully.',
            [
                'user' => $this->userService->getUser($user),
            ],
            200
        );
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $user = $this->userService->updateUser(
            $user,
            $request->validated()
        );

        return $this->successMessage(
            'User updated successfully.',
            ['user' => $user],
            200
        );
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->userService->deleteUser($user);

        return $this->successMessage(
            'User deleted successfully.',
            [],
            200
        );
    }

    /**
     * Permanently delete the specified user.
     */
    public function forceDelete(User $user): JsonResponse
    {
        $this->authorize('forceDelete', $user);
        $this->userService->forceDelete($user);

        return $this->successMessage(
            'User permanently deleted successfully.',
            ['data' => 'success'],
            200
        );
    }

    /**
     * Restore the specified user.
     */
    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);
        $this->userService->restore($user);

        return $this->successMessage(
            'User restored successfully.',
            [],
            200
        );
    }

    /**
     * Display trashed users.
     */
    public function trashedUsers(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', User::class);
        $filters = $request->only([
            'name',
            'email',
        ]);

        $users = $this->userService->trashedUsers($filters);


        return $this->successMessage(
            'Trashed users retrieved successfully.',
            ['users' => $users],
            200
        );
    }

    /**
     * Restore all trashed users.
     */
    public function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAny', User::class);
        $this->userService->restoreAll();

        return $this->successMessage(
            'All users restored successfully.',
            ['data' => 'success'],
            200
        );
    }

    /**
     * Permanently delete all trashed users.
     */
    public function emptyTrash(): JsonResponse
    {
        $this->authorize('forceDeleteAny', User::class);
        $this->userService->emptyTrash();

        return $this->successMessage(
            'Trash emptied successfully.',
            ['data' => 'success'],
            200
        );
    }
}
