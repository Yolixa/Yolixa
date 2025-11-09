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
            'email'         => ['required','email','max:150','unique:users,email'],
            'blockchain_id' => ['required','integer'],
            'wallet_type'   => ['required','string','max:50'],
            'public_key'    => ['required','string','max:150','unique:users,public_key'],
            'trust_tx_hash' => ['nullable','string','max:100'], // proof ke liye optional
        ]);

        $ref = strtoupper(Str::random(10));
        while (User::where('referral_key', $ref)->exists()) {
            $ref = strtoupper(Str::random(10));
        }

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'blockchain_id' => $data['blockchain_id'],
            'wallet_type'   => $data['wallet_type'],
            'public_key'    => $data['public_key'],
            'role'          => 'creator',
            'status'        => true,
            'referral_key'  => $ref,
        ]);

        $refUrl = route('creator.referral', ['code' => $user->referral_key]);

        return response()->json([
            'status'       => true,
            'message'      => 'Creator registered successfully.',
            'referral_url' => $refUrl,
        ]);
    }

    public function referralLanding(string $code)
    {
        $creator = User::where('referral_key', $code)
            ->where('role', 'creator')
            ->firstOrFail();

        return view('creator.referral', compact('creator'));
    }
}
