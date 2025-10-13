<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function userWalletConnect(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:100',
            'status' => 'required|boolean',
        ]);

        $existingUser = User::where('public_key', $request->address)->first();

        if ($existingUser) {
            return response()->json([
                'success' => true,
                'message' => 'Welcome back! Your wallet is already connected.',
                'public_key' => $existingUser->public_key,
            ]);
        }

        $user = new User();
        $user->public_key = $request->address;
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Your wallet has been connected successfully.',
            'public_key' => $user->public_key,
        ]);
    }
}
