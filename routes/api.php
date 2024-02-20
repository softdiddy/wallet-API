<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;


// User registration and API key generation
Route::post('/register', [WalletController::class, 'register']);

// Wallet operations
Route::middleware('auth:api')->group(function () {
    Route::post('/create-wallet', [WalletController::class, 'createWallet']);
    Route::post('/debit-wallet', [WalletController::class, 'debitWallet']);
    Route::get('/transaction-history', [WalletController::class, 'getTransactionHistory']);
});