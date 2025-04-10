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
    Route::resource('projects', ProjectController::class);
    Route::controller(ProjectController::class)->group(function(){
        Route::get('/project-managers', 'getProjectManagers');
        Route::delete('/projects/{id}/image', 'removeProjectImage');
    });
});

