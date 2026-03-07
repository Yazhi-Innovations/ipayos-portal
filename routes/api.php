<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TransactionController;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JwtAuthenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken as JwtRefreshToken;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(JwtAuthenticate::class);
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware(JwtRefreshToken::class);
    Route::get('me', [AuthController::class, 'me'])->middleware(JwtAuthenticate::class);
});

Route::middleware(JwtAuthenticate::class)->group(function () {
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('transactions/search', [TransactionController::class, 'search']);
});

