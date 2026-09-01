<?php

use App\Http\Controllers\Api\V1\RolesAndPermissions\PermissionController;
use App\Http\Controllers\Api\V1\RolesAndPermissions\RoleController;
use Illuminate\Support\Facades\Route;

/**
 * ################################### Roles and Permissions API Routes ###################################
 */

Route::prefix('v1')->middleware(['can:adminJob'])->group(function () {

    /**
     * Role routes
     */
    Route::prefix('roles')->group(function () {

        Route::get('/', [RoleController::class, 'index'])->name('roles.index');

        Route::get('/{role}', [RoleController::class, 'show'])
            ->name('roles.show');

        Route::post('/assign/users/{user}/role/{role}', [RoleController::class, 'assignRoleToUser'])
            ->name('roles.assign');

        Route::post('/remove/users/{user}/role/{role}', [RoleController::class, 'removeRoleFromUser'])
            ->name('roles.remove');

        Route::post('/sync/users/{user}', [RoleController::class, 'syncRolesToUser'])
            ->name('roles.sync');
    });

    /**
     * Permission routes
     */
    Route::prefix('permissions')->group(function () {

        Route::get('/', [PermissionController::class, 'index'])
            ->name('permissions.index');

        Route::get('/{permission}', [PermissionController::class, 'show'])
            ->name('permissions.show');

        Route::post('/assign/users/{user}/permission/{permission}', [PermissionController::class, 'givePermissionToUser'])
            ->name('permissions.assign');

        Route::post('/remove/users/{user}/permission/{permission}', [PermissionController::class, 'removePermissionFromUser'])
            ->name('permissions.remove');

        Route::post('/sync/permission/{permission}', [PermissionController::class, 'syncRolesToPermission'])
            ->name('permissions.sync');
    });
});
