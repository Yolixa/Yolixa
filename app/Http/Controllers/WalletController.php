<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Soneso\StellarSDK\Crypto\KeyPair;

class WalletController extends Controller
{
    public function getChallenge(Request $request)
    {
        $request->validate(['address' => 'required|string']);
        $challenge = \Illuminate\Support\Str::random(64);
        $request->session()->put('wallet_challenge_' . $request->address, $challenge);
        
        return response()->json([
            'success' => true,
            'challenge' => $challenge
        ]);
    }

    public function userWalletConnect(Request $request)
    {
        try {
            Log::info('Wallet Connect flow initiated.', [
                'address' => $request->address,
                'blockchainId' => $request->blockchainId,
                'walletId' => $request->walletId
            ]);

            $validator = Validator::make($request->all(), [
                'address' => 'required|string|max:100',
                'blockchainId' => 'required|integer|exists:blockchains,id',
                'walletId' => 'required|integer|exists:wallet_types,id',
                'status' => 'required|boolean',
                'signature' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for Wallet Connect API logic.', ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Verification error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cryptographic Verification
            $challenge = $request->session()->get('wallet_challenge_' . $request->address);
            
            if (!$challenge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Challenge missing or expired. Please refresh the page and try again.'
                ], 401);
            }

            try {
                $kp = KeyPair::fromAccountId($request->address);
                
                // Detect Hex vs Base64 signature length
                $sigStr = $request->signature;
                if (ctype_xdigit($sigStr) && strlen($sigStr) === 128) {
                    $rawSig = hex2bin($sigStr);
                } else {
                    $rawSig = base64_decode($sigStr);
                }
                
                if (strlen($rawSig) !== 64) {
                    Log::warning('Signature is incorrectly formatted.', ['address' => $request->address, 'len' => strlen($rawSig)]);
                    return response()->json(['success' => false, 'message' => 'Malformed signature length.'], 401);
                }

                $verified = $kp->verifySignature($rawSig, $challenge);
                
                // Freighter uses a specific prefix, if raw fails, check prefixed message
                if (!$verified) {
                    $freighterPrefix = "Stellar Signed Message:\n" . $challenge;
                    $verified = $kp->verifySignature($rawSig, $freighterPrefix);
                }

                if (!$verified) {
                    // Try with hashing the payload if it's signed over hash
                    $verified = $kp->verifySignature($rawSig, hash('sha256', "Stellar Signed Message:\n" . $challenge, true));
                }
                
                if (!$verified) {
                    Log::warning('Signature verification failed.', ['address' => $request->address]);
                    return response()->json(['success' => false, 'message' => 'Invalid wallet signature.'], 401);
                }
            } catch (\Exception $e) {
                Log::error('Signature parsing error.', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => 'Signature verification process failed.'], 401);
            }

            $request->session()->forget('wallet_challenge_' . $request->address);

            Log::info('Payload validation and Signature verification successful. Checking DB for existing user.');

            $existingUser = User::where('public_key', $request->address)->first();

            if ($existingUser) {
                Log::info("Existing User located [{$existingUser->id}]. Updating status.");
                $existingUser->status = 1;
                $existingUser->update();

                $existingWallet = Wallet::where('public_key', $request->address)->first();

                if (!$existingWallet) {
                    Log::warning("User [{$existingUser->id}] existed, but no matching wallet found in database. Recreating mapping.");
                    Wallet::create([
                        'user_id' => $existingUser->id,
                        'blockchain_id' => $request->blockchainId,
                        'wallet_type_id' => $request->walletId,
                        'public_key' => $request->address,
                    ]);
                }

                // Removed `true` parameter to prevent the 'remember_token' SQL logic break.
                Auth::login($existingUser);
                Log::info('User re-authenticated into application session successfully.', ['user_id' => $existingUser->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! Your wallet is connected.',
                    'public_key' => $existingUser->public_key,
                    'role' => $existingUser->role,
                ]);
            }

            Log::info('No mapped User found. Instantiating brand new user profile via wallet.');

            $user = new User();
            $user->public_key = $request->address;
            $user->status = $request->status;
            $user->role = 'fan'; // Default new users to fan
            $user->save();

            Log::info('New User created mapping completed.', ['user_id' => $user->id]);

            $wallet = new Wallet();
            $wallet->user_id = $user->id;   
            $wallet->blockchain_id = $request->blockchainId;
            $wallet->wallet_type_id = $request->walletId;
            $wallet->public_key = $request->address;
            $wallet->save();
            
            Log::info('New Wallet entity saved alongside the user instance.');

            // Removed `true` parameter to prevent the 'remember_token' SQL logic break.
            Auth::login($user);
            Log::info('Brand new Wallet User authenticated successfully.', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Your wallet has been connected successfully.',
                'public_key' => $user->public_key,
                'role' => $user->role,
            ]);

        } catch (\Exception $e) {
            Log::error('CRITICAL: Server Error during Wallet Connect.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while trying to save the wallet.'
            ], 500);
        }
    }

    public function disconnectWallet(Request $request)
    {
        try {
            Log::info('Initiating Wallet session disconnect logic.', ['address' => $request->address]);

            $validator = Validator::make($request->all(), [
                'address' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('public_key', $request->address)->first();

            if (!$user) {
                Log::warning('Disconnect called for unknown User payload.', ['address' => $request->address]);
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $user->status = 0;
            $user->save();
            Log::info("User [{$user->id}] marked as disconnected in database.");
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('Session effectively terminated and disconnected.');

            return response()->json([
                'success' => true,
                'message' => 'Wallet disconnected successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('CRITICAL: Server Error during Wallet Disconnect.', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect wallet securely.'
            ], 500);
        }
    }
}
