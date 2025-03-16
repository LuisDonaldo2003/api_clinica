<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\Rol\RolesController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Admin\Staff\StaffsController;
use App\Http\Controllers\Admin\Doctor\DoctorsController;
use App\Http\Controllers\Admin\Doctor\SpecialityController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\AppointmentPayController;
use App\Http\Controllers\Appointment\AppointmentAttentioncontroller;

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
    Route::resource("roles",RolesController::class);

    Route::get("staffs/config",[StaffsController::class,"config"]);
    Route::post("staffs/{id}",[StaffsController::class,"update"]);
    Route::resource("staffs",StaffsController::class);
    // 
    Route::resource("specialities",SpecialityController::class);
    // 
    Route::get("doctors/profile/{id}",[DoctorsController::class,"profile"]);
    Route::get("doctors/config",[DoctorsController::class,"config"]);
    Route::post("doctors/{id}",[DoctorsController::class,"update"]);
    Route::resource("doctors",DoctorsController::class);
    // 
    Route::get("patients/profile/{id}",[PatientController::class,"profile"]);
    Route::post("patients/{id}",[PatientController::class,"update"]);
    Route::resource("patients",PatientController::class);
    // 
    Route::get("appointmet/config",[AppointmentController::class,"config"]);
    Route::get("appointmet/patient",[AppointmentController::class,"query_patient"]);
    Route::post("appointmet/filter",[AppointmentController::class,"filter"]);
    Route::post("appointmet/calendar",[AppointmentController::class,"calendar"]);
    Route::resource("appointmet",AppointmentController::class);

    Route::resource("appointmet-pay",AppointmentPayController::class);
    Route::resource("appointmet-attention",AppointmentAttentioncontroller::class);

});

