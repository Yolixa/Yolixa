<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StellarService;
use App\Services\TipService;

use App\Services\XlmAmount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TipController extends Controller
{
    private StellarService $stellarService;
    private TipService $tipService;
    private XlmAmount $amounts;

    public function __construct(StellarService $stellarService, TipService $tipService, XlmAmount $amounts)
    {
        $this->stellarService = $stellarService;
        $this->tipService = $tipService;
        $this->amounts = $amounts;
    }

    public function getPreview(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'string', 'regex:/^(0|[1-9]\d*)(?:\.\d{1,7})?$/'],
            'asset'  => ['required', Rule::in($this->stellarService->supportedTipAssets())],
        ]);

        try {
            $atomic = $this->amounts->decimalToAtomic((string) $request->amount);
            $this->amounts->validateWithinConfiguredLimits($atomic);
            $split = $this->stellarService->calculateSplitAmounts($this->amounts->atomicToDecimal($atomic));

            return response()->json([
                'success' => true,
                'rate' => null,
                'gross_ylx' => null,
                'fee_ylx' => null,
                'creator_payout_ylx' => null,
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
        if (config('yolixa.tip_execution_mode') !== 'classic') {
            return response()->json(['success' => false, 'message' => 'Classic tipping is not the selected execution mode.'], 409);
        }

        $request->validate([
            'amount'      => ['required', 'string', 'regex:/^(0|[1-9]\d*)(?:\.\d{1,7})?$/'],
            'destination' => 'required|string',
            'asset'       => ['required', Rule::in($this->stellarService->supportedTipAssets())],
            'sender'      => 'required|string',
        ]);

        $sender = strtoupper(trim((string) $request->sender));
        $destination = strtoupper(trim((string) $request->destination));

        $user = Auth::user();
        if (!$user || $user->public_key !== $sender) {
            return response()->json(['success' => false, 'message' => 'Connected wallet does not match the authenticated Yolixa session.'], 403);
        }

        if (!$this->stellarService->isValidPublicKey($sender)) {
            return response()->json(['success' => false, 'message' => 'Invalid sender wallet.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($destination)) {
            return response()->json(['success' => false, 'message' => 'Invalid creator wallet.'], 422);
        }

        if ($destination === $sender) {
            return response()->json(['success' => false, 'message' => 'Self tip blocked.'], 400);
        }

        try {
            $atomic = $this->amounts->decimalToAtomic((string) $request->amount);
            $this->amounts->validateWithinConfiguredLimits($atomic);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $result = $this->stellarService->buildTipXdr(
            $sender,
            $destination,
            $request->asset,
            $this->amounts->atomicToDecimal($atomic)
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function submitTransaction(Request $request)
    {
        if (config('yolixa.tip_execution_mode') !== 'classic') {
            return response()->json(['success' => false, 'message' => 'Classic tipping is not the selected execution mode.'], 409);
        }

        $request->validate([
            'signedXdr' => 'required|string',
            'sender_key' => 'required|string',
        ]);

        $sender = strtoupper(trim((string) $request->sender_key));

        $user = Auth::user();
        if (!$user || $user->public_key !== $sender) {
            return response()->json(['success' => false, 'message' => 'Connected wallet does not match the authenticated Yolixa session.'], 403);
        }

        if (!$this->stellarService->isValidPublicKey($sender)) {
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
        if (config('yolixa.tip_execution_mode') !== 'classic') {
            return response()->json(['success' => false, 'message' => 'Classic tipping is not the selected execution mode.'], 409);
        }

        $request->validate([
            'tx_hash'      => ['required', 'string', 'regex:/^[A-Fa-f0-9]{64}$/', 'unique:tips,tx_hash'],
            'amount'       => ['required', 'string', 'regex:/^(0|[1-9]\d*)(?:\.\d{1,7})?$/'],
            'asset'        => ['required', Rule::in($this->stellarService->supportedTipAssets())],
            'receiver_id'  => 'required|exists:users,id',
            'sender_key'   => 'required|string',
            'message'      => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
            'sender_name'  => 'nullable|string|max:100',
        ]);

        $sender = strtoupper(trim((string) $request->sender_key));
        $txHash = strtolower(trim((string) $request->tx_hash));

        $user = Auth::user();
        if (!$user || $user->public_key !== $sender) {
            return response()->json(['success' => false, 'message' => 'Connected wallet does not match the authenticated Yolixa session.'], 403);
        }

        $receiver = User::findOrFail($request->receiver_id);
        if ($receiver->role !== 'creator') {
            return response()->json(['success' => false, 'message' => 'Receiver must be a creator.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($receiver->public_key)) {
            return response()->json(['success' => false, 'message' => 'Creator wallet is invalid.'], 422);
        }

        if (!$this->stellarService->isValidPublicKey($sender)) {
            return response()->json(['success' => false, 'message' => 'Invalid sender wallet.'], 422);
        }

        if ($receiver->public_key === $sender) {
            return response()->json(['success' => false, 'message' => 'Self tip blocked.'], 400);
        }

        try {
            $atomic = $this->amounts->decimalToAtomic((string) $request->amount);
            $this->amounts->validateWithinConfiguredLimits($atomic);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $result = $this->tipService->recordTipSecurely([
            'tx_hash'             => $txHash,
            'amount'              => $this->amounts->atomicToDecimal($atomic),
            'asset'               => $request->asset,
            'receiver_id'         => $request->receiver_id,
            'sender_key'          => $sender,
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
