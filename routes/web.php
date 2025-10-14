<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WalletController;

Route::get('/', [WebController::class, 'index'])->name('index');
Route::get('/whitepaper', [WebController::class, 'whitepaper'])->name('whitepaper');
Route::get('/get-wallets/{blockchain}', [WebController::class, 'getWallets']);
Route::post('/save-wallet', [WalletController::class, 'userWalletConnect'])->name('save.wallet');
Route::post('/disconnect-wallet', [WalletController::class, 'disconnectWallet']);
