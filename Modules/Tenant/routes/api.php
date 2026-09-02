<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\Api\V1\DepartmentController;
use Modules\Tenant\Http\Controllers\Api\V1\PositionController;
use Modules\Tenant\Http\Controllers\Api\V1\ProjectController;
use Modules\Tenant\Http\Controllers\Api\V1\RolesAndPermissions\PermissionController;
use Modules\Tenant\Http\Controllers\Api\V1\RolesAndPermissions\RoleController;
use Modules\Tenant\Http\Controllers\Api\V1\TaskController;
use Modules\Tenant\Http\Controllers\Api\V1\TeamController;
use Modules\Tenant\Http\Controllers\Api\V1\TenantUserController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

Route::middleware([
    InitializeTenancyByDomain::class,
    'auth:sanctum',
])->prefix('v1')->group(function () {


    //tenantUsers
    Route::prefix('tenantUsers')->group(function () {
        Route::get('/', [TenantUserController::class, 'index']);
        Route::post('/', [TenantUserController::class, 'store']);
        Route::get('/{tenantUser}', [TenantUserController::class, 'show']);
        Route::put('/{tenantUser}', [TenantUserController::class, 'update']);
        Route::delete('/{tenantUser}', [TenantUserController::class, 'destroy']);
    });

    //Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->name('roles.index');

        Route::get('/{role}', [RoleController::class, 'show'])
            ->name('roles.show');

        Route::post('/assign/tenantUsers/{tenantUser}/role/{role}', [RoleController::class, 'assignRoleToUser'])
            ->name('roles.assign');

        Route::post('/remove/tenantUsers/{tenantUser}/role/{role}', [RoleController::class, 'removeRoleFromUser'])
            ->name('roles.remove');

        Route::post('/sync/tenantUsers/{tenantUser}', [RoleController::class, 'syncRolesToUser'])
            ->name('roles.sync');

        Route::get('/tenantUsers/{tenantUser}', [RoleController::class, 'getRoleToTenantUser'])
            ->name('roles.getRoleToTenantUser');
    })->middleware(['can:ownerJob']);

    //permissions
    Route::prefix('permissions')->group(function () {

        Route::get('/', [PermissionController::class, 'index'])
            ->name('permissions.index');

        Route::get('/{permission}', [PermissionController::class, 'show'])
            ->name('permissions.show');

        Route::post('/assign/TenantUsers/{tenantUser}/permission/{permission}', [PermissionController::class, 'givePermissionToUser'])
            ->name('permissions.assign');

        Route::post('/remove/TenantUsers/{tenantUser}/permission/{permission}', [PermissionController::class, 'removePermissionFromUser'])
            ->name('permissions.remove');

        Route::post('/sync/permission/{permission}', [PermissionController::class, 'syncRolesToPermission'])
            ->name('permissions.sync');

        Route::get('/TenantUsers/{tenantUser}', [PermissionController::class, 'getUserPermissions'])
            ->name('permissions.getUserPermissions');
    })->middleware(['can:ownerJob']);


    //Departments
    Route::prefix('departments')->group(function () {


        Route::post('/restore-all', [DepartmentController::class, 'restoreAll'])
            ->name('departments.restoreAll')->withTrashed();
        Route::delete('/delete-all', [DepartmentController::class, 'forceDeleteAll'])
            ->name('departments.forceDeleteAll')->withTrashed();
        Route::get('/trashed', [DepartmentController::class, 'getAllTrashed'])
            ->name('departments.getAllTrashed')->withTrashed();
        Route::get('/trashed/{department}', [DepartmentController::class, 'getTrashed'])
            ->name('departments.getTrashed')->withTrashed();
        Route::delete('/{department}/force-delete', [DepartmentController::class, 'forceDelete'])
            ->name('departments.forceDelete')->withTrashed();
        Route::post('/{department}/restore', [DepartmentController::class, 'restore'])
            ->name('departments.restore')->withTrashed();

        Route::get('/{department}', [DepartmentController::class, 'show'])
            ->name('departments.show');
        Route::post('/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])
            ->name('departments.destroy');
        Route::get('/', [DepartmentController::class, 'index'])
            ->name('departments.index');
        Route::post('/', [DepartmentController::class, 'store'])
            ->name('departments.store');
    });


    //Positions
    Route::prefix('positions')->group(function () {


        Route::post('/restore-all', [PositionController::class, 'restoreAll'])
            ->name('positions.restoreAll')->withTrashed();
        Route::delete('/force-delete-all', [PositionController::class, 'forceDeleteAll'])
            ->name('positions.forceDeleteAll')->withTrashed();
        Route::get('/trashed', [PositionController::class, 'getAllTrashed'])
            ->name('positions.getAllTrashed')->withTrashed();
        Route::get('/trashed/{position}', [PositionController::class, 'getTrashed'])
            ->name('positions.getTrashed')->withTrashed();
        Route::delete('/{position}/force-delete', [PositionController::class, 'forceDelete'])
            ->name('positions.forceDelete')->withTrashed();
        Route::post('/{position}/restore', [PositionController::class, 'restore'])
            ->name('positions.restore')->withTrashed();

        Route::get('/', [PositionController::class, 'index'])
            ->name('positions.index');
        Route::post('/', [PositionController::class, 'store'])
            ->name('positions.store');
        Route::get('/{position}', [PositionController::class, 'show'])
            ->name('position.show');
        Route::put('/{position}', [PositionController::class, 'update'])
            ->name('position.update');
        Route::delete('/{position}', [PositionController::class, 'destroy'])
            ->name('position.destroy');
    });

    //Teams
    Route::prefix('teams')->group(function () {
        Route::get('/', [TeamController::class, 'index'])
            ->name('teams.index');
        Route::post('/', [TeamController::class, 'store'])
            ->name('teams.store');
        Route::get('/{team}', [TeamController::class, 'show'])
            ->name('teams.show');
        Route::post('/{team}', [TeamController::class, 'update'])
            ->name('teams.update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])
            ->name('teams.destroy');
    });

    //projects

    Route::prefix('projects')->group(function () {


        Route::post('/restore-all', [ProjectController::class, 'restoreAll'])
            ->name('project.restoreAll')->withTrashed();
        Route::delete('/force-delete-all', [ProjectController::class, 'forceDeleteAll'])
            ->name('project.forceDeleteAll')->withTrashed();
        Route::get('/trashed', [ProjectController::class, 'getAllTrashed'])
            ->name('project.getAllTrashed')->withTrashed();
        Route::get('/trashed/{project}', [ProjectController::class, 'getTrashed'])
            ->name('project.getTrashed')->withTrashed();
        Route::delete('/{project}/force-delete', [ProjectController::class, 'forceDelete'])
            ->name('project.forceDelete')->withTrashed();
        Route::post('/{project}/restore', [ProjectController::class, 'restore'])
            ->name('project.restore')->withTrashed();

        Route::get('/', [ProjectController::class, 'index'])
            ->name('project.index');
        Route::post('/', [ProjectController::class, 'store'])
            ->name('project.store');
        Route::get('/{project}', [ProjectController::class, 'show'])
            ->name('project.show');
        Route::post('/{project}', [ProjectController::class, 'update'])
            ->name('project.update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->name('project.destroy');

        //Actions
        Route::post('/{project}/onhold', [ProjectController::class, 'onHold'])
            ->name('project.onHold');
        Route::post('/{project}/resume', [ProjectController::class, 'resume'])
            ->name('project.resume');
        Route::post('/{project}/cancel', [ProjectController::class, 'cancel'])
            ->name('project.cancel');
        Route::post('/{project}/complete', [ProjectController::class, 'complete'])
            ->name('project.complete');
    });

    //Tasks
    Route::prefix('tasks')->group(function () {

        Route::post('restore-all', [TaskController::class, 'restoreAll'])
            ->name('tasks.restoreAll')->withTrashed();
        Route::delete('force-delete-all', [TaskController::class, 'forceDeleteAll'])
            ->name('tasks.forceDeleteAll')->withTrashed();
        Route::get('trashed', [TaskController::class, 'getTrashedTasks'])
            ->name('tasks.getAllTrashed')->withTrashed();
        Route::get('trashed/{task}', [TaskController::class, 'getTrashed'])
            ->name('tasks.getTrashed')->withTrashed();
        Route::delete('{task}/force-delete', [TaskController::class, 'forceDelete'])
            ->name('tasks.forceDelete')->withTrashed();
        Route::post('{task}/restore', [TaskController::class, 'restore'])
            ->name('tasks.restore')->withTrashed();

        Route::get('/', [TaskController::class, 'index'])
            ->name('tasks.index');
        Route::post('/', [TaskController::class, 'store'])
            ->name('tasks.store');
        Route::get('/{task}', [TaskController::class, 'show'])
            ->name('tasks.show');
        Route::post('/{task}', [TaskController::class, 'update'])
            ->name('tasks.update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])
            ->name('tasks.destroy');

        //Task Actions
        Route::post('{task}/complete', [TaskController::class, 'complete'])
            ->name('tasks.complete');
        Route::post('{task}/cancel', [TaskController::class, 'cancel'])
            ->name('tasks.cancel');
        Route::post('on-hold/{task}', [TaskController::class, 'onHold'])
            ->name('tasks.onHold');
    });
});
