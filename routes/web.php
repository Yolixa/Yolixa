<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WalletController;
use Soneso\StellarSDK\StellarSDK;

Route::get('/', [WebController::class, 'index'])->name('index');
Route::get('/whitepaper', [WebController::class, 'whitepaper'])->name('whitepaper');
Route::get('/get-wallets/{blockchain}', [WebController::class, 'getWallets']);
Route::post('/save-wallet', [WalletController::class, 'userWalletConnect'])->name('save.wallet');
Route::post('/disconnect-wallet', [WalletController::class, 'disconnectWallet']);


Route::get('/stellar-test', function () {
     try {
        // Initialize SDK for Stellar Test Network
        $sdk = StellarSDK::getTestNetInstance();

        // Replace this with any valid TESTNET public key
        $accountId = 'GBTOBXOTQJFNAYHBO7WQ7H2XFYU3BW46JX5ISCFLP7JDTSX5MN42TEXS';

         // Fetch account
        $account = $sdk->requestAccount($accountId);

        // 🚩 Balances: ArrayIterator / Traversable — iterate directly
        $balances = [];
        foreach ($account->getBalances() as $balance) {
            $balances[] = [
                'asset_type' => method_exists($balance, 'getAssetType') ? $balance->getAssetType() : null,
                'asset_code' => method_exists($balance, 'getAssetCode') ? $balance->getAssetCode() : null,
                'balance'    => method_exists($balance, 'getBalance') ? $balance->getBalance() : null,
                'liquidity_pool_id' => method_exists($balance, 'getLiquidityPoolId') ? $balance->getLiquidityPoolId() : null,
            ];
        }

        return response()->json([
            'account_id' => $account->getAccountId(),
            'sequence'   => $account->getSequenceNumber(),
            'balances'   => $balances,
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
