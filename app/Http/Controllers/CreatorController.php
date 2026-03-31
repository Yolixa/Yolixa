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
             return response()->json(['status' => false, 'message' => 'Email already taken.'], 422);
        }

        $user = User::where('public_key', $data['public_key'])->first();

        $ref = strtoupper(Str::random(10));
        while (User::where('referral_key', $ref)->exists()) {
            $ref = strtoupper(Str::random(10));
        }

        if ($user) {
            $user->update([
                'name'         => $data['name'],
                'email'        => $data['email'],
                'role'         => 'creator',
                'status'       => 1,
                'referral_key' => $user->referral_key ?? $ref,
            ]);
        } else {
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

    public function dashboard(string $publicKey)
    {
        $creator = User::where('public_key', $publicKey)->firstOrFail();
        
        $tips = \App\Models\Tip::where('receiver_id', $creator->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('creator.dashboard', compact('creator', 'tips'));
    }
}
