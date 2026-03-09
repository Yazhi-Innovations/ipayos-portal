<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\SettlementController;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('home');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/settlement', [SettlementController::class, 'index'])->name('settlement.index')->middleware('settlement');
    Route::get('/settlement/csv', [SettlementController::class, 'exportCsv'])->name('settlement.export.csv')->middleware('settlement');
    Route::get('/settlement/user/{user}', [SettlementController::class, 'downloadUserPdf'])->name('settlement.user.pdf')->middleware('settlement');
    Route::post('/settlement/user/settle', [SettlementController::class, 'settleUser'])->name('settlement.user.settle')->middleware('settlement');
});
