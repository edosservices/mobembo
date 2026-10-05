<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active', 'password.fresh'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [PlatformController::class, 'dashboard']);
        Route::get('/projects', [PlatformController::class, 'projects']);
        Route::post('/projects/{project}/invest', [PlatformController::class, 'invest']);
        Route::get('/investments', [PlatformController::class, 'investments']);
        Route::post('/deposits', [PlatformController::class, 'deposit']);
        Route::post('/withdrawals/quote', [PlatformController::class, 'withdrawalQuote']);
        Route::post('/withdrawals', [PlatformController::class, 'withdraw']);
        Route::get('/transactions', [PlatformController::class, 'transactions']);
        Route::get('/notifications', [PlatformController::class, 'notifications']);
    });
});
