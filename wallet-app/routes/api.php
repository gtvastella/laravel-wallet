<?php

use App\Http\Controllers\Api\ApiDocumentationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/v1/docs', [ApiDocumentationController::class, 'index']);

Route::post('/v1/register', [AuthController::class, 'register']);
Route::post('/v1/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/logout', [AuthController::class, 'logout']);

    Route::get('/v1/wallet', [WalletController::class, 'show']);
    Route::post('/v1/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/v1/wallet/transfer', [WalletController::class, 'transfer']);

    Route::get('/v1/transactions', [TransactionController::class, 'index']);
    Route::get('/v1/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/v1/transactions/{id}/reverse', [TransactionController::class, 'reverse']);
});
