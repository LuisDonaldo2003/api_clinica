<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\Rol\RolesController;
use App\Http\Controllers\Admin\Staff\StaffsController;

Route::group([
    'prefix' => 'auth',
    // 'middleware' => ['role:admin','permission:publish articles'],
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
    Route::post('/regis', [AuthController::class, 'regis']);

});

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => ['role:admin','permission:publish articles'],
], function ($router) {
    Route::resource("roles", RolesController::class);

    Route::get("staffs/config", [StaffsController::class, 'config']);
    Route::post("staffs/{id}", [StaffsController::class,'update']);
    Route::resource("staffs", StaffsController::class);

});
