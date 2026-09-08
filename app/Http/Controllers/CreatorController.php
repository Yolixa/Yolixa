<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletType;
use Soneso\StellarSDK\Crypto\KeyPair;

class CreatorController extends Controller
{
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('creator_registration_started', [
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $data = $request->validate([
            'name'          => ['required','string','max:100'],
            'email'         => ['required','email','max:150'],
            'blockchain_id' => ['required','integer','exists:blockchains,id'],
            'wallet_type'   => ['required','string','max:50'],
            'public_key'    => ['required','string','max:150'],
            'trust_tx_hash' => ['nullable','string','max:100'],
        ]);

        try {
            KeyPair::fromAccountId($data['public_key']);
        } catch (\Throwable) {
            return response()->json(['status' => false, 'message' => 'Invalid Stellar public key.'], 422);
        }

        $authenticatedUser = \Illuminate\Support\Facades\Auth::user();
        if (!$authenticatedUser || $authenticatedUser->public_key !== $data['public_key']) {
            return response()->json([
                'status' => false,
                'message' => 'Creator registration must match the authenticated wallet session.',
            ], 403);
        }

        // Email uniqueness check manually to avoid complex rules
        if (User::where('email', $data['email'])->where('public_key', '!=', $data['public_key'])->exists()) {
             \Illuminate\Support\Facades\Log::warning('creator_registration_email_conflict', [
                 'user_id' => $authenticatedUser->id,
             ]);
             return response()->json(['status' => false, 'message' => 'Email already taken.'], 422);
        }

        $user = $authenticatedUser;

        $ref = strtoupper(Str::random(10));
        while (User::where('referral_key', $ref)->exists()) {
            $ref = strtoupper(Str::random(10));
        }

        if ($user) {
            \Illuminate\Support\Facades\Log::info('fan_upgraded_to_creator', ['user_id' => $user->id]);
            $user->update([
                'name'         => $data['name'],
                'email'        => $data['email'],
                'role'         => 'creator',
                'status'       => 1,
                'referral_key' => $user->referral_key ?? $ref,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info('creator_record_created_from_wallet', [
                'public_key_suffix' => substr($data['public_key'], -8),
            ]);
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'public_key'    => $data['public_key'],
                'role'          => 'creator',
                'status'        => 1,
                'referral_key'  => $ref,
            ]);
        }

        $walletType = WalletType::where('blockchain_id', $data['blockchain_id'])
            ->where(function ($query) use ($data) {
                $query->where('name', $data['wallet_type'])
                    ->orWhere('slug', Str::slug($data['wallet_type']));
            })
            ->first();

        if (!$walletType && is_numeric($data['wallet_type'])) {
            $walletType = WalletType::where('blockchain_id', $data['blockchain_id'])->find($data['wallet_type']);
        }

        if ($walletType) {
            Wallet::updateOrCreate(
                ['public_key' => $user->public_key],
                [
                    'user_id' => $user->id,
                    'blockchain_id' => $data['blockchain_id'],
                    'wallet_type_id' => $walletType->id,
                ]
            );
        }

        $refUrl = route('creator.referral', ['code' => $user->referral_key]);
        \Illuminate\Support\Facades\Log::info('creator_registration_completed', ['user_id' => $user->id]);

        return response()->json([
            'status'       => true,
            'message'      => 'Creator registered successfully.',
            'referral_url' => $refUrl,
            'user'         => $user
        ]);
    }

    public function referralLanding(string $code)
    {
        $creator = User::where('referral_key', $code)
            ->where('role', 'creator')
            ->firstOrFail();

        return view('creator.referral', compact('creator'));
    }

    public function showProfile(string $username)
    {
        $creator = User::where('username', $username)
            ->where('role', 'creator')
            ->firstOrFail();

        return view('creator.referral', compact('creator'));
    }

    public function dashboard(string $publicKey = null)
    {
        \Illuminate\Support\Facades\Log::info('creator_dashboard_requested', [
            'requested_public_key_suffix' => $publicKey ? substr($publicKey, -8) : null,
        ]);

        // Require authentication. 
        // This stops anyone from just passing a publicKey in the URL unauthenticated.
        $creator = \Illuminate\Support\Facades\Auth::user();

        if (!$creator) {
            \Illuminate\Support\Facades\Log::warning('creator_dashboard_unauthenticated', [
                'requested_public_key_suffix' => $publicKey ? substr($publicKey, -8) : null,
            ]);
            abort(403, 'Unauthorized access. Please specify and connect your wallet.');
        }

        if ($creator->role !== 'creator') {
            \Illuminate\Support\Facades\Log::warning('creator_dashboard_non_creator', ['user_id' => $creator->id, 'role' => $creator->role]);
            abort(403, 'Unauthorized access. You must be a registered creator to view this panel.');
        }

        if ($publicKey && $creator->public_key !== $publicKey) {
            \Illuminate\Support\Facades\Log::warning('creator_dashboard_key_mismatch', [
                'user_id' => $creator->id, 
                'auth_public_key_suffix' => substr($creator->public_key, -8),
                'requested_public_key_suffix' => substr($publicKey, -8),
            ]);
            abort(404, 'Dashboard not found for the requested public key.');
        }
        
        $totalConfirmedXlmReceived = \App\Models\Tip::where('receiver_id', $creator->id)
            ->where('status', 'confirmed')
            ->where('asset', 'XLM')
            ->sum('creator_payout_amount');

        $totalTips = \App\Models\Tip::where('receiver_id', $creator->id)->count();

        $tips = \App\Models\Tip::where('receiver_id', $creator->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        \Illuminate\Support\Facades\Log::info("Dashboard loaded successfully.", ['user_id' => $creator->id]);
        return view('creator.dashboard', compact('creator', 'tips', 'totalConfirmedXlmReceived', 'totalTips'));
    }

    public function updateProfile(Request $request)
    {
        $creator = \Illuminate\Support\Facades\Auth::user();

        if (!$creator || $creator->role !== 'creator') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($request->filled('username')) {
            $request->merge([
                'username' => Str::slug($request->input('username')),
            ]);
        } elseif ($request->has('username')) {
            $request->merge(['username' => null]);
        }

        $data = $request->validate([
            'username' => 'nullable|string|min:3|max:50|alpha_dash|not_in:admin,api,auth,creator,dashboard,disconnect-wallet,get-wallets,r,save-wallet,storage,up,whitepaper|unique:users,username,' . $creator->id,
            'bio' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:50',
            'preferred_tip_asset' => 'nullable|in:XLM',
            'custom_thank_you_message' => 'nullable|string|max:255',
            'min_tip_amount' => 'nullable|numeric|min:0.1',
            'goal_title' => 'nullable|string|max:100',
            'goal_amount' => 'nullable|numeric|min:1',
            // Image handling could be added later using storage
        ]);

        $creator->update($data);
        $creator = $creator->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'creator' => $creator,
            'profile_url' => $creator->username
                ? route('creator.profile', ['username' => $creator->username])
                : route('creator.referral', ['code' => $creator->referral_key]),
        ]);
    }

}
