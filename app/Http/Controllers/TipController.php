<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StellarService;
use App\Services\TipService;

use App\Services\ConversionService;
use Illuminate\Validation\Rule;

class TipController extends Controller
{
    private StellarService $stellarService;
    private TipService $tipService;
    private ConversionService $conversionService;

    public function __construct(StellarService $stellarService, TipService $tipService, ConversionService $conversionService)
    {
        $this->stellarService = $stellarService;
        $this->tipService = $tipService;
        $this->conversionService = $conversionService;
    }

    public function getPreview(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . config('yolixa.min_payment_amount', 0.0000001) . '|max:' . config('yolixa.max_payment_amount', 1000),
            'asset'  => ['required', Rule::in($this->stellarService->supportedTipAssets())],
        ]);

        try {
            $conversion = $this->conversionService->convertToYlx($request->asset, floatval($request->amount));
            $fees = $this->conversionService->calculateFees($conversion['converted_amount'], $request->asset);
            $split = $this->stellarService->calculateSplitAmounts((string) $request->amount);

            return response()->json([
                'success' => true,
                'rate' => $conversion['rate'],
                'gross_ylx' => $conversion['converted_amount'],
                'fee_ylx' => $fees['fee_amount'],
                'creator_payout_ylx' => $fees['net_payout'],
                'gross_amount' => $split['gross_amount'],
                'platform_fee' => $split['platform_fee'],
                'creator_receives' => $split['creator_payout_amount'],
                'asset' => $request->asset,
                'network' => config('yolixa.network', 'testnet'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function buildXdr(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:' . config('yolixa.min_payment_amount', 0.0000001) . '|max:' . config('yolixa.max_payment_amount', 1000),
            'destination' => 'required|string',
            'asset'       => ['required', Rule::in($this->stellarService->supportedTipAssets())],
            'sender'      => 'required|string',
        ]);

        if (!$this->stellarService->isValidPublicKey($request->sender)) {
            return response()->json(['success' => false, 'message' => 'Invalid sender wallet.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($request->destination)) {
            return response()->json(['success' => false, 'message' => 'Invalid creator wallet.'], 422);
        }

        if ($request->destination === $request->sender) {
            return response()->json(['success' => false, 'message' => 'Self tip blocked.'], 400);
        }

        $result = $this->stellarService->buildTipXdr(
            $request->sender,
            $request->destination,
            $request->asset,
            floatval($request->amount)
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function submitTransaction(Request $request)
    {
        $request->validate([
            'signedXdr' => 'required|string',
            'sender_key' => 'required|string',
        ]);

        if (!$this->stellarService->isValidPublicKey($request->sender_key)) {
            return response()->json(['success' => false, 'message' => 'Invalid sender wallet.'], 422);
        }

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
            'amount'       => 'required|numeric|min:' . config('yolixa.min_payment_amount', 0.0000001) . '|max:' . config('yolixa.max_payment_amount', 1000),
            'asset'        => ['required', Rule::in($this->stellarService->supportedTipAssets())],
            'receiver_id'  => 'required|exists:users,id',
            'sender_key'   => 'required|string',
            'message'      => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
            'sender_name'  => 'nullable|string|max:100',
        ]);

        $receiver = User::findOrFail($request->receiver_id);
        if ($receiver->role !== 'creator') {
            return response()->json(['success' => false, 'message' => 'Receiver must be a creator.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($receiver->public_key)) {
            return response()->json(['success' => false, 'message' => 'Creator wallet is invalid.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($request->sender_key)) {
            return response()->json(['success' => false, 'message' => 'Invalid sender wallet.'], 422);
        }

        if ($receiver->public_key === $request->sender_key) {
            return response()->json(['success' => false, 'message' => 'Self tip blocked.'], 400);
        }

        $result = $this->tipService->recordTipSecurely([
            'tx_hash'             => $request->tx_hash,
            'amount'              => floatval($request->amount),
            'asset'               => $request->asset,
            'receiver_id'         => $request->receiver_id,
            'sender_key'          => $request->sender_key,
            'receiver_public_key' => $receiver->public_key,
            'message'             => $request->message,
            'is_anonymous'        => $request->is_anonymous,
            'sender_name'         => $request->sender_name,
        ]);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

}
