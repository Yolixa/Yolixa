<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StellarService;
use App\Services\TipService;

class TipController extends Controller
{
    private StellarService $stellarService;
    private TipService $tipService;

    public function __construct(StellarService $stellarService, TipService $tipService)
    {
        $this->stellarService = $stellarService;
        $this->tipService = $tipService;
    }

    public function buildXdr(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:1',
            'destination' => 'required|string',
            'asset'       => 'required|in:XLM,YLX',
            'sender'      => 'required|string',
        ]);

        if ($request->destination === $request->sender) {
            return response()->json(['success' => false, 'message' => 'You cannot tip yourself.'], 400);
        }

        $platformFee = $this->tipService->calculatePlatformFee($request->asset, $request->amount);
        
        $result = $this->stellarService->buildTipXdr(
            $request->sender,
            $request->destination,
            $request->asset,
            floatval($request->amount),
            $platformFee
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function submitTransaction(Request $request)
    {
        $request->validate(['signedXdr' => 'required|string']);

        $result = $this->stellarService->submitTransaction($request->signedXdr);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function recordTip(Request $request)
    {
        $request->validate([
            'tx_hash'      => 'required|string|unique:tips,tx_hash',
            'amount'       => 'required|numeric',
            'asset'        => 'required|string',
            'receiver_id'  => 'required|exists:users,id',
            'sender_key'   => 'nullable|string',
            'message'      => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
            'sender_name'  => 'nullable|string|max:100',
        ]);

        $receiver = User::findOrFail($request->receiver_id);
        $platformFee = $this->tipService->calculatePlatformFee($request->asset, $request->amount);

        $result = $this->tipService->recordTipSecurely([
            'tx_hash'             => $request->tx_hash,
            'amount'              => floatval($request->amount),
            'asset'               => $request->asset,
            'receiver_id'         => $request->receiver_id,
            'sender_key'          => $request->sender_key,
            'receiver_public_key' => $receiver->public_key,
            'platform_fee'        => $platformFee,
            'message'             => $request->message,
            'is_anonymous'        => $request->is_anonymous,
            'sender_name'         => $request->sender_name,
        ]);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function buildTrustlineXdr(Request $request)
    {
        $request->validate([
            'sender' => 'required|string',
        ]);

        $result = $this->stellarService->buildTrustlineXdr($request->sender);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}
