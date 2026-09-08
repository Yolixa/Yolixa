<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Soneso\StellarSDK\Crypto\KeyPair;
use App\Services\StellarConfigurationService;

class WalletController extends Controller
{
    public function __construct(private StellarConfigurationService $stellarConfiguration)
    {
    }

    public function sessionStatus(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'authenticated' => (bool) $user,
            'public_key' => $user?->public_key,
            'role' => $user?->role,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function getChallenge(Request $request)
    {
        $request->validate(['address' => 'required|string|max:100']);
        $address = strtoupper(trim($request->address));
        if (!$this->stellarConfiguration->isValidPublicKey($address)) {
            return response()->json(['success' => false, 'message' => 'Invalid Stellar public key.'], 422);
        }

        $challenge = \Illuminate\Support\Str::random(64);
        $request->session()->put('wallet_challenge_' . $address, [
            'challenge' => $challenge,
            'expires_at' => now()->addSeconds((int) config('yolixa.wallet_challenge_ttl_seconds', 300))->timestamp,
        ]);
        
        return response()->json([
            'success' => true,
            'challenge' => $challenge
        ]);
    }

    public function userWalletConnect(Request $request)
    {
        try {
            Log::info('Wallet Connect flow initiated.', [
                'blockchainId' => $request->blockchainId,
                'walletId' => $request->walletId
            ]);

            if (is_array($request->input('signature'))) {
                $request->merge([
                    'signature' => $this->extractSignatureString($request->input('signature')),
                ]);
            }
            $request->merge(['address' => strtoupper(trim((string) $request->address))]);

            $validator = Validator::make($request->all(), [
                'address' => 'required|string|max:100',
                'blockchainId' => 'nullable|integer|exists:blockchains,id',
                'walletId' => 'nullable|integer|exists:wallet_types,id',
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

            try {
                $kp = KeyPair::fromAccountId($request->address);
            } catch (\Throwable) {
                return response()->json(['success' => false, 'message' => 'Invalid Stellar public key.'], 422);
            }

            // Cryptographic Verification
            $challengePayload = $request->session()->get('wallet_challenge_' . $request->address);
            
            if (!$challengePayload || !is_array($challengePayload) || empty($challengePayload['challenge'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Challenge missing or expired. Please refresh the page and try again.'
                ], 401);
            }

            if (($challengePayload['expires_at'] ?? 0) < now()->timestamp) {
                $request->session()->forget('wallet_challenge_' . $request->address);

                return response()->json([
                    'success' => false,
                    'message' => 'Challenge expired. Please try again.'
                ], 401);
            }

            $challenge = $challengePayload['challenge'];

            try {
                // Detect Hex vs Base64 signature length
                $sigStr = $request->signature;
                if (ctype_xdigit($sigStr) && strlen($sigStr) === 128) {
                    $rawSig = hex2bin($sigStr);
                } else {
                    $rawSig = base64_decode($sigStr, true);
                }
                
                if ($rawSig === false || strlen($rawSig) !== 64) {
                    Log::warning('wallet_signature_malformed', ['address_suffix' => substr($request->address, -8)]);
                    return response()->json(['success' => false, 'message' => 'Malformed signature length.'], 401);
                }

                $verified = $kp->verifySignature($rawSig, $challenge);
                
                // Stellar browser wallets may sign the SEP-53 prefixed challenge payload.
                if (!$verified) {
                    $stellarMessagePayload = "Stellar Signed Message:\n" . $challenge;
                    $verified = $kp->verifySignature($rawSig, $stellarMessagePayload);
                }

                if (!$verified) {
                    $verified = $kp->verifySignature($rawSig, hash('sha256', "Stellar Signed Message:\n" . $challenge, true));
                }
                
                if (!$verified) {
                    Log::warning('wallet_signature_verification_failed', ['address_suffix' => substr($request->address, -8)]);
                    return response()->json(['success' => false, 'message' => 'Invalid wallet signature.'], 401);
                }
            } catch (\Exception $e) {
                Log::error('wallet_signature_parsing_failed', ['exception' => $e::class]);
                return response()->json(['success' => false, 'message' => 'Signature verification process failed.'], 401);
            }

            $request->session()->forget('wallet_challenge_' . $request->address);

            Log::info('Payload validation and Signature verification successful. Checking DB for existing user.');

            $existingUser = User::where('public_key', $request->address)->first();
            $existingWallet = Wallet::where('public_key', $request->address)->first();
            $blockchainId = $request->filled('blockchainId') ? $request->blockchainId : $existingWallet?->blockchain_id;
            $walletTypeId = $request->filled('walletId') ? $request->walletId : $existingWallet?->wallet_type_id;

            if (!$blockchainId || !$walletTypeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet metadata missing. Please reconnect from the wallet modal once.'
                ], 422);
            }

            if ($existingUser) {
                Log::info('existing_wallet_user_reauthenticated', ['user_id' => $existingUser->id]);
                $existingUser->status = 1;
                $existingUser->update();

                Wallet::updateOrCreate(
                    ['public_key' => $request->address],
                    [
                        'user_id' => $existingUser->id,
                        'blockchain_id' => $blockchainId,
                        'wallet_type_id' => $walletTypeId,
                    ]
                );

                $request->session()->regenerate();
                Auth::login($existingUser, true);
                Log::info('User re-authenticated into application session successfully.', ['user_id' => $existingUser->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! Your wallet is connected.',
                    'public_key' => $existingUser->public_key,
                    'role' => $existingUser->role,
                    'blockchain_id' => $blockchainId,
                    'wallet_type_id' => $walletTypeId,
                ]);
            }

            Log::info('creating_wallet_user_profile');

            $user = new User();
            $user->public_key = $request->address;
            $user->status = $request->status;
            $user->role = 'fan'; // Default new users to fan
            $user->save();

            Log::info('New User created mapping completed.', ['user_id' => $user->id]);

            Wallet::updateOrCreate(
                    ['public_key' => $request->address],
                    [
                        'user_id' => $user->id,
                        'blockchain_id' => $blockchainId,
                        'wallet_type_id' => $walletTypeId,
                    ]
                );
            
            Log::info('New Wallet entity saved alongside the user instance.');

            $request->session()->regenerate();
            Auth::login($user, true);
            Log::info('Brand new Wallet User authenticated successfully.', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Your wallet has been connected successfully.',
                'public_key' => $user->public_key,
                'role' => $user->role,
                'blockchain_id' => $blockchainId,
                'wallet_type_id' => $walletTypeId,
            ]);

        } catch (\Exception $e) {
            Log::error('wallet_connect_failed', ['exception' => $e::class]);
            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while trying to save the wallet.'
            ], 500);
        }
    }

    public function disconnectWallet(Request $request)
    {
        try {
            Log::info('wallet_disconnect_requested');

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

            $user = Auth::user();

            if (!$user || $user->public_key !== $request->address) {
                Log::warning('wallet_disconnect_rejected', [
                    'requested_address_suffix' => substr((string) $request->address, -8),
                    'user_id' => $user?->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You can only disconnect the wallet authenticated in this session.',
                ], 403);
            }

            $user->status = 0;
            $user->save();
            Log::info('wallet_user_marked_disconnected', ['user_id' => $user->id]);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('Session effectively terminated and disconnected.');

            return response()->json([
                'success' => true,
                'message' => 'Wallet disconnected successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('wallet_disconnect_failed', ['exception' => $e::class]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect wallet securely.'
            ], 500);
        }
    }

    private function extractSignatureString(array $signature): ?string
    {
        foreach (['signature', 'signedMessage', 'signed_message', 'signedPayload'] as $key) {
            if (isset($signature[$key]) && is_string($signature[$key])) {
                return $signature[$key];
            }

            if (isset($signature[$key]) && is_array($signature[$key])) {
                $nested = $this->extractSignatureString($signature[$key]);
                if ($nested) {
                    return $nested;
                }
            }
        }

        if (isset($signature['data']) && is_array($signature['data'])) {
            return $this->extractSignatureString($signature['data']);
        }

        if (array_is_list($signature) && count($signature) === 64) {
            return base64_encode(pack('C*', ...$signature));
        }

        return null;
    }
}
