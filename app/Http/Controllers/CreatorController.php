<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class CreatorController extends Controller
{
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Initiating creator registration process.', ['payload' => $request->except('trust_tx_hash')]);

        $data = $request->validate([
            'name'          => ['required','string','max:100'],
            'email'         => ['required','email','max:150'],
            'blockchain_id' => ['required','integer'],
            'wallet_type'   => ['required','string','max:50'],
            'public_key'    => ['required','string','max:150'],
            'trust_tx_hash' => ['nullable','string','max:100'],
        ]);

        // Email uniqueness check manually to avoid complex rules
        if (User::where('email', $data['email'])->where('public_key', '!=', $data['public_key'])->exists()) {
             \Illuminate\Support\Facades\Log::warning('Creator registration failed due to existing email address.', ['email' => $data['email']]);
             return response()->json(['status' => false, 'message' => 'Email already taken.'], 422);
        }

        $user = User::where('public_key', $data['public_key'])->first();

        $ref = strtoupper(Str::random(10));
        while (User::where('referral_key', $ref)->exists()) {
            $ref = strtoupper(Str::random(10));
        }

        if ($user) {
            \Illuminate\Support\Facades\Log::info("Existing fan user located. Upgrading to creator role.", ['user_id' => $user->id]);
            $user->update([
                'name'         => $data['name'],
                'email'        => $data['email'],
                'role'         => 'creator',
                'status'       => 1,
                'referral_key' => $user->referral_key ?? $ref,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info("No existing user found. Creating a brand new creator record.", ['public_key' => $data['public_key']]);
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'public_key'    => $data['public_key'],
                'role'          => 'creator',
                'status'        => 1,
                'referral_key'  => $ref,
            ]);
        }

        $refUrl = route('creator.referral', ['code' => $user->referral_key]);
        \Illuminate\Support\Facades\Log::info("Creator registration completed successfully. Handing off to frontend redirect phase.", ['user_id' => $user->id]);

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

        $recentTips = \App\Models\Tip::where('receiver_id', $creator->id)
            ->where('status', 'confirmed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $tipsSum = \App\Models\Tip::where('receiver_id', $creator->id)
            ->where('status', 'confirmed')
            ->sum('amount'); // This is a bit complex if assets differ, but we can do a naive sum or rely on converted_ylx_amount
        
        $convertedTotal = \App\Models\Tip::where('receiver_id', $creator->id)
            ->where('status', 'confirmed')
            ->sum('converted_ylx_amount');

        return view('creator.profile', compact('creator', 'recentTips', 'convertedTotal'));
    }

    public function dashboard(string $publicKey = null)
    {
        \Illuminate\Support\Facades\Log::info("Dashboard access initiated.", ['requested_public_key' => $publicKey]);

        // Require authentication. 
        // This stops anyone from just passing a publicKey in the URL unauthenticated.
        $creator = \Illuminate\Support\Facades\Auth::user();

        if (!$creator) {
            \Illuminate\Support\Facades\Log::warning("Unauthorized dashboard access attempt. User not logged in.", ['requested_public_key' => $publicKey]);
            abort(403, 'Unauthorized access. Please specify and connect your wallet.');
        }

        if ($creator->role !== 'creator') {
            \Illuminate\Support\Facades\Log::warning("Non-creator role attempted to access dashboard.", ['user_id' => $creator->id, 'role' => $creator->role]);
            abort(403, 'Unauthorized access. You must be a registered creator to view this panel.');
        }

        if ($publicKey && $creator->public_key !== $publicKey) {
            \Illuminate\Support\Facades\Log::warning("Dashboard public key mismatch. User tried to view another dashboard.", [
                'user_id' => $creator->id, 
                'auth_public_key' => $creator->public_key, 
                'requested_public_key' => $publicKey
            ]);
            abort(404, 'Dashboard not found for the requested public key.');
        }
        
        $tips = \App\Models\Tip::where('receiver_id', $creator->id)
            ->orderBy('created_at', 'desc')
            ->get();

        \Illuminate\Support\Facades\Log::info("Dashboard loaded successfully.", ['user_id' => $creator->id]);
        return view('creator.dashboard', compact('creator', 'tips'));
    }

    public function updateProfile(Request $request)
    {
        $creator = \Illuminate\Support\Facades\Auth::user();

        if (!$creator || $creator->role !== 'creator') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'username' => 'nullable|string|max:50|unique:users,username,' . $creator->id,
            'bio' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:50',
            'preferred_tip_asset' => 'nullable|in:XLM,YLX',
            'custom_thank_you_message' => 'nullable|string|max:255',
            'min_tip_amount' => 'nullable|numeric|min:0.1',
            'goal_title' => 'nullable|string|max:100',
            'goal_amount' => 'nullable|numeric|min:1',
            // Image handling could be added later using storage
        ]);

        $creator->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'creator' => $creator
        ]);
    }

    public function claimRewards(Request $request)
    {
        $creator = \Illuminate\Support\Facades\Auth::user();

        if (!$creator || $creator->role !== 'creator') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        if ($creator->ylx_claimable_balance < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient claimable balance.'], 400);
        }

        $processingFee = config('yolixa.claim_fee', 0); // Flat or percentage processing fee placeholder

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $creator->decrement('ylx_claimable_balance', $request->amount);
            $creator->increment('ylx_claimed_total', $request->amount);

            $claim = \App\Models\RewardClaim::create([
                'user_id' => $creator->id,
                'amount' => $request->amount,
                'fee_deducted' => $processingFee,
                'status' => 'pending',
                'notes' => 'Awaiting admin processing',
            ]);

            \Illuminate\Support\Facades\DB::commit();

            \Illuminate\Support\Facades\Log::channel('stellar')->info("Reward Claim Requested by {$creator->id} for {$request->amount} YLX.");

            return response()->json([
                'success' => true,
                'message' => 'Reward claim submitted successfully. It will be processed shortly.',
                'claim' => $claim
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Reward Claim Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error.'], 500);
        }
    }
}
