<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



/**
 * ###################################Auth API routes###################################
 * ################################################################################################
 */
Route::prefix('v1')->group(function () {


    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('user.login');
        Route::post('/register', [AuthController::class, 'register'])->name('user.register');


        Route::post('/logout', [AuthController::class, 'logout'])->name('user.logout')->middleware('auth:sanctum');
    });



    /**
     *
     * ###################################User API routes###################################
     * ################################################################################################
     *
     */
    Route::middleware('auth:sanctum')->prefix('users')->group(function () {


        Route::delete('/force_delete/{user}', [UserController::class, 'forceDelete'])->name('users.force_delete');
        Route::post('/restore/{user}', [UserController::class, 'restore'])
            ->name('users.restore')->withTrashed();
        Route::get('/trashed-users', [UserController::class, 'trashedUsers'])->name('users.trashedUsers');
        Route::put('/restore_all', [UserController::class, 'restoreAll'])->name('users.restore_all')
            ->withTrashed();
        Route::delete('/empty_trash', [UserController::class, 'emptyTrash'])
            ->name('users.empty_trash')->withTrashed();

        Route::get('/', [UserController::class, 'index'])->name('users.all');
        Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });


    //#################### Profile routes #####################################
    Route::middleware(['auth:sanctum'])->prefix('profiles')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profiles.index');
        Route::get('/{profile}', [ProfileController::class, 'show'])->name('profiles.show');
        Route::post('/{profile}', [ProfileController::class, 'update'])->name('profiles.update');
    });
});
