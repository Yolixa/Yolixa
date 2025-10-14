<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function userWalletConnect(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:100',
            'blockchainId' => 'required|integer|exists:blockchains,id',
            'walletId' => 'required|integer|exists:wallet_types,id',
            'status' => 'required|boolean',
        ]);

        $existingUser = User::where('public_key', $request->address)->first();

        if ($existingUser) {
            $existingUser->status = 1;
            $existingUser->update();
            
            $existingWallet = Wallet::where('public_key', $request->address)->first();

            if (!$existingWallet) {
                Wallet::create([
                    'user_id' => $existingUser->id,
                    'blockchain_id' => $request->blockchainId,
                    'wallet_type_id' => $request->walletId,
                    'public_key' => $request->address,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! Your wallet is connected.',
                'public_key' => $existingUser->public_key,
            ]);
        }

        $user = new User();
        $user->public_key = $request->address;
        $user->status = $request->status;
        $user->save();

        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->blockchain_id = $request->blockchainId;
        $wallet->wallet_type_id = $request->walletId;
        $wallet->public_key = $request->address;
        $wallet->save();

        return response()->json([
            'success' => true,
            'message' => 'Your wallet has been connected successfully.',
            'public_key' => $user->public_key,
        ]);
    }

    public function disconnectWallet(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:100',
        ]);

        $user = User::where('public_key', $request->address)->first();

         if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->status = 0;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Wallet disconnected successfully.',
        ]);
    }
}
