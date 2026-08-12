<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\SorobanTipController;
// use Soneso\StellarSDK\StellarSDK;
// use Soneso\StellarSDK\Asset;
// use Soneso\StellarSDK\Crypto\KeyPair;

use App\Http\Controllers\TipController;

Route::get('/', [WebController::class, 'index'])->name('index');
Route::get('/whitepaper', [WebController::class, 'whitepaper'])->name('whitepaper');
Route::get('/get-wallets/{blockchain}', [WebController::class, 'getWallets']);
Route::get('/auth/session', [WalletController::class, 'sessionStatus']);
Route::post('/auth/challenge', [WalletController::class, 'getChallenge'])->middleware('throttle:wallet-auth');
Route::post('/save-wallet', [WalletController::class, 'userWalletConnect'])->middleware('throttle:wallet-auth')->name('save.wallet');
Route::post('/disconnect-wallet', [WalletController::class, 'disconnectWallet'])->middleware('auth');
Route::post('/creator/register', [CreatorController::class, 'store'])->middleware('auth')->name('creator.store');
Route::get('/r/{code}', [CreatorController::class, 'referralLanding'])->name('creator.referral');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/{publicKey}', [CreatorController::class, 'dashboard'])->name('creator.dashboard');
    Route::post('/creator/update-profile', [CreatorController::class, 'updateProfile'])->name('creator.update_profile');
    Route::post('/creator/claim-rewards', [CreatorController::class, 'claimRewards'])->name('creator.claim_rewards');
    
    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/creators/{id}/manage', [\App\Http\Controllers\AdminController::class, 'manageCreator'])->name('admin.manage_creator');
    });
});
// Tip & Reward API routes
Route::prefix('api/tip')->middleware('throttle:tip-api')->group(function() {
    Route::post('/preview', [TipController::class, 'getPreview']);
    Route::post('/build-xdr', [TipController::class, 'buildXdr']);
    Route::post('/submit', [TipController::class, 'submitTransaction']);
    Route::post('/record', [TipController::class, 'recordTip']);
});

Route::prefix('api/soroban')->middleware('throttle:tip-api')->group(function () {
    Route::get('/config', [SorobanTipController::class, 'config']);
    Route::post('/tip/intent', [SorobanTipController::class, 'intent'])->middleware('auth');
    Route::post('/tip/submitted', [SorobanTipController::class, 'submitted'])->middleware('auth');
    Route::post('/tip/confirm', [SorobanTipController::class, 'confirm'])->middleware('auth');
});

Route::get('/{username}', [CreatorController::class, 'showProfile'])
    ->where('username', '[A-Za-z0-9][A-Za-z0-9_-]{2,49}')
    ->name('creator.profile');
