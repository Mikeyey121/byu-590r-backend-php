<?php

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(RegisterController::class)->group(function(): void{
    Route::post('register','register');
    Route::post('login','login');
    Route::post('logout','logout');
});

Route::middleware('auth:sanctum')->group(function() {
    Route::controller(UserController::class)->group(function(){
        Route::get('user', 'getUser');
        Route::post('user/upload_avatar', 'uploadAvatar');
        Route::delete('user/remove_avatar','removeAvatar');
        Route::post('user/send_verification_email','sendVerificationEmail');
        Route::post('user/change_email', 'changeEmail');
    });
});

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/project-managers', [ProjectController::class, 'getProjectManagers']);
Route::post('/projects', [ProjectController::class, 'store']);
Route::put('/projects/{id}', [ProjectController::class, 'update']);
Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
Route::delete('/projects/{id}/image', [ProjectController::class, 'removeProjectImage']);