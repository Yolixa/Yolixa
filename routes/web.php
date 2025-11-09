<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WalletController;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;

Route::get('/', [WebController::class, 'index'])->name('index');
Route::get('/whitepaper', [WebController::class, 'whitepaper'])->name('whitepaper');
Route::get('/get-wallets/{blockchain}', [WebController::class, 'getWallets']);
Route::post('/save-wallet', [WalletController::class, 'userWalletConnect'])->name('save.wallet');
Route::post('/disconnect-wallet', [WalletController::class, 'disconnectWallet']);
Route::post('/creator/register', [CreatorController::class, 'store'])->name('creator.store');
Route::get('/r/{code}', [CreatorController::class, 'referralLanding'])->name('creator.referral');


// Route::get('/stellar-test', function () {
//      try {
//         // Initialize SDK for Stellar Test Network
//         $sdk = StellarSDK::getTestNetInstance();

//         // Replace this with any valid TESTNET public key
//         $accountId = 'GBJK3TMW6YSNYYVJGERXAQINEKY6AWPDRF3IMY4QY6AAZNGBUYT5PRG5';

//          // Fetch account
//         $account = $sdk->requestAccount($accountId);

//         // Balances: ArrayIterator / Traversable — iterate directly
//         $balances = [];
//         foreach ($account->getBalances() as $balance) {
//             $balances[] = [
//                 'asset_type' => method_exists($balance, 'getAssetType') ? $balance->getAssetType() : null,
//                 'asset_code' => method_exists($balance, 'getAssetCode') ? $balance->getAssetCode() : null,
//                 'balance'    => method_exists($balance, 'getBalance') ? $balance->getBalance() : null,
//                 'liquidity_pool_id' => method_exists($balance, 'getLiquidityPoolId') ? $balance->getLiquidityPoolId() : null,
//             ];
//         }

//         return response()->json([
//             'account_id' => $account->getAccountId(),
//             'sequence'   => $account->getSequenceNumber(),
//             'balances'   => $balances,
//         ]);

//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });

// Route::get('/get-ylx', function () {
//     $sdk = StellarSDK::getTestNetInstance();
//     $issuerSecret = env('ISSUER_SECRET_KEY');
//     $issuerKeypair = KeyPair::fromSeed($issuerSecret);
//     $issuerAccount = $sdk->requestAccount($issuerKeypair->getAccountId());

//     $assetCode = 'YLX';
//     $asset = Asset::createNonNativeAsset($assetCode, $issuerKeypair->getAccountId());

//     return response()->json([
//         'asset_code' => $asset->getCode(),
//         'issuer' => $issuerKeypair->getAccountId(),
//         'network' => 'testnet',
//     ]);
// });

