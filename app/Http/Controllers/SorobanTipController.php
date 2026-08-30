<?php

namespace App\Http\Controllers;

use App\Services\SorobanTipRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SorobanTipController extends Controller
{
    public function __construct(private SorobanTipRouterService $tips)
    {
    }

    public function config()
    {
        return response()->json([
            'success' => true,
            'config' => $this->tips->publicConfig(),
        ]);
    }

    public function intent(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'amount' => 'required|string|max:40',
            'asset' => 'nullable|string|max:20',
            'sender' => 'nullable|string|max:100',
        ]);

        $fan = Auth::user();
        if (!$fan) {
            return response()->json(['success' => false, 'message' => 'Wallet authentication session expired.'], 401);
        }

        try {
            $intent = $this->tips->createIntent($fan, $request->only(['receiver_id', 'amount', 'asset', 'sender']));

            return response()->json($this->tips->responseForIntent($intent));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::channel('security')->error('Soroban tip intent failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not prepare Soroban tip.'], 500);
        }
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'intent_id' => 'required|integer|exists:tip_intents,id',
            'tx_hash' => 'required|string|max:120',
        ]);

        $fan = Auth::user();
        if (!$fan) {
            return response()->json(['success' => false, 'message' => 'Wallet authentication session expired.'], 401);
        }

        try {
            $result = $this->tips->confirm($fan, (int) $request->intent_id, trim((string) $request->tx_hash));
            if (!($result['success'] ?? false)) {
                return response()->json($result, ($result['retryable'] ?? false) ? 202 : 422);
            }

            $tip = $result['tip'];
            $intent = $result['intent'];

            return response()->json([
                'success' => true,
                'tip' => $tip,
                'proof' => [
                    'tx_hash' => $tip->tx_hash,
                    'network' => config('yolixa.network', 'testnet'),
                    'router_contract_id' => $tip->router_contract_id,
                    'token_contract_id' => $tip->token_contract_id,
                    'contract_tip_id' => (string) $tip->contract_tip_id,
                    'creator_payout' => $tip->creator_payout_amount,
                    'platform_fee' => $tip->platform_fee,
                    'verified_on_chain' => true,
                    'intent_id' => $intent->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::channel('security')->error('Soroban tip confirmation failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not confirm Soroban tip.'], 500);
        }
    }

    public function submitted(Request $request)
    {
        $request->validate([
            'intent_id' => 'required|integer|exists:tip_intents,id',
            'tx_hash' => 'required|string|max:120',
        ]);

        $fan = Auth::user();
        if (!$fan) {
            return response()->json(['success' => false, 'message' => 'Wallet authentication session expired.'], 401);
        }

        try {
            $result = $this->tips->submitted($fan, (int) $request->intent_id, trim((string) $request->tx_hash));
            $intent = $result['intent'];

            return response()->json([
                'success' => true,
                'intent' => [
                    'id' => $intent->id,
                    'status' => $intent->status,
                    'tx_hash' => $intent->tx_hash,
                ],
                'already_confirmed' => (bool) ($result['already_confirmed'] ?? false),
            ], ($result['already_confirmed'] ?? false) ? 200 : 202);
        } catch (ValidationException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::channel('security')->error('Soroban tip submission failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not record submitted Soroban tip.'], 500);
        }
    }
}
