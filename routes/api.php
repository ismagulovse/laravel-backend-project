<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Policy\PermissionController;
use App\Http\Controllers\Api\Policy\RoleController;
use App\Http\Controllers\Api\Policy\RolePermissionController;
use App\Http\Controllers\Api\Policy\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {

    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('guest.check')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
    });

    Route::middleware('auth.check')->group(function (): void {
        Route::get('me',       [AuthController::class, 'me']);
        Route::get('permissions', [AuthController::class, 'permissions']);
        Route::post('out',     [AuthController::class, 'out']);
        Route::get('tokens',   [AuthController::class, 'tokens']);
        Route::post('out_all', [AuthController::class, 'outAll']);
    });

    Route::middleware('refresh.check')->group(function (): void {
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Публичный список пользователей доступен гостю.
Route::get('ref/user', [UserRoleController::class, 'indexUsers'])
    ->middleware('permission.check:get-list-user');

Route::middleware('auth.check')->prefix('ref')->group(function (): void {
    Route::prefix('policy')->group(function (): void {
        // CRUD ролей.
        Route::get('role', [RoleController::class, 'index'])->middleware('permission.check:get-list-role');
        Route::get('role/{role}', [RoleController::class, 'show'])->middleware('permission.check:read-role');
        Route::post('role', [RoleController::class, 'store'])->middleware('permission.check:create-role');
        Route::put('role/{role}', [RoleController::class, 'update'])->middleware('permission.check:update-role');
        Route::delete('role/{role}', [RoleController::class, 'destroy'])->middleware('permission.check:delete-role');
        Route::patch('role/{role}/soft', [RoleController::class, 'restore'])->middleware('permission.check:restore-role');

        // CRUD разрешений.
        Route::get('permission', [PermissionController::class, 'index'])->middleware('permission.check:get-list-permission');
        Route::get('permission/{permission}', [PermissionController::class, 'show'])->middleware('permission.check:read-permission');
        Route::post('permission', [PermissionController::class, 'store'])->middleware('permission.check:create-permission');
        Route::put('permission/{permission}', [PermissionController::class, 'update'])->middleware('permission.check:update-permission');
        Route::delete('permission/{permission}', [PermissionController::class, 'destroy'])->middleware('permission.check:delete-permission');
        Route::patch('permission/{permission}/soft', [PermissionController::class, 'restore'])->middleware('permission.check:restore-permission');

        // Связь role <-> permission.
        Route::get('role/{role}/permission', [RolePermissionController::class, 'listRolePermissions'])->middleware('permission.check:read-role');
        Route::post('role/{role}/permission', [RolePermissionController::class, 'attach'])->middleware('permission.check:update-role');
        Route::get('role/{role}/permission/{permission}', [RolePermissionController::class, 'showRolePermission'])->middleware('permission.check:read-role,update-role,read-role');
        Route::delete('role/{role}/permission/{permission}', [RolePermissionController::class, 'detach'])->middleware('permission.check:update-role');
        Route::patch('role/{role}/permission/{permission}/soft', [RolePermissionController::class, 'softDelete'])->middleware('permission.check:update-role');
        Route::patch('role/{role}/permission/{permission}/restore', [RolePermissionController::class, 'restore'])->middleware('permission.check:update-role');
    });

    // Связь user <-> role.
    Route::get('user/{user}/role', [UserRoleController::class, 'listUserRoles'])->middleware('permission.check:read-user');
    Route::get('user/{user}/permission', [UserRoleController::class, 'listUserPermissions'])->middleware('permission.check:read-user');
    Route::post('user/{user}/role', [UserRoleController::class, 'attach'])->middleware('permission.check:update-user');
    Route::get('user/{user}/role/{role}', [UserRoleController::class, 'showUserRole'])->middleware('permission.check:read-user');
    Route::delete('user/{user}/role/{role}', [UserRoleController::class, 'detach'])->middleware('permission.check:update-user');
    Route::patch('user/{user}/role/{role}/soft', [UserRoleController::class, 'softDelete'])->middleware('permission.check:update-user');
    Route::patch('user/{user}/role/{role}/restore', [UserRoleController::class, 'restore'])->middleware('permission.check:update-user');
});


































//Как вызывается мидлвееер
//почему вызывается в таком формате middleware('permission.check:update-user')
//как предать не одно разрешение а несколько  
//как реализуется выбор или между разрешениями и ролями
// у определенного пользователя посмотреть список разрешений и разрешений
// несколько ролей для пользователя 
// Полноый разбор кода
