<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\Request;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Network;
use Illuminate\Support\Facades\DB;

class TipController extends Controller
{
    private $sdk;
    private $network;

    public function __construct()
    {
        $this->sdk = StellarSDK::getTestNetInstance();
        $this->network = Network::testnet();
    }

    public function buildXdr(Request $request)
    {
        // SECURITY: Minimum amount enforced to prevent negative values, dust attacks, or edge cases.
        $request->validate([
            'amount'      => 'required|numeric|min:1',
            'destination' => 'required|string',
            'asset'       => 'required|in:XLM', // Restrict to XLM strictly while in dev mode
            'sender'      => 'required|string',
        ]);

        try {
            // Check if sender actually exists and is funded on the Testnet
            try {
                $senderAccount = $this->sdk->requestAccount($request->sender);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Sender wallet is not funded on the Testnet. Please fund it using Friendbot.'], 400);
            }
            
            $asset = Asset::native();

            // Calculate Fee: 1.5%
            $platformFeeFloat = floatval($request->amount) * 0.015;
            $creatorAmountFloat = floatval($request->amount) - $platformFeeFloat;

            // SECURITY // FORMATTING: Soneso SDK requires precise string numeric formatting (max 7 decimals)
            $platformFee = number_format($platformFeeFloat, 7, '.', '');
            $creatorAmount = number_format($creatorAmountFloat, 7, '.', '');

            $txBuilder = new TransactionBuilder($senderAccount);
            
            // Operation 1: Payment to Creator
            $paymentToCreator = (new PaymentOperationBuilder($request->destination, $asset, $creatorAmount))->build();
            $txBuilder->addOperation($paymentToCreator);

            // Operation 2: Payment to Platform (Fee)
            $platformPublic = env('ISSUER_PUBLIC_KEY');
            if (!empty($platformPublic)) {
                $paymentToPlatform = (new PaymentOperationBuilder($platformPublic, $asset, $platformFee))->build();
                $txBuilder->addOperation($paymentToPlatform);
            }

            $transaction = $txBuilder->build();
            $xdr = $transaction->toEnvelopeXdrBase64();

            return response()->json([
                'success' => true,
                'xdr'     => $xdr,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to build transaction: ' . $e->getMessage()], 500);
        }
    }

    public function submitTransaction(Request $request)
    {
        $request->validate(['signedXdr' => 'required|string']);

        try {
            $transaction = \Soneso\StellarSDK\AbstractTransaction::fromEnvelopeBase64XdrString($request->signedXdr);
            $response = $this->sdk->submitTransaction($transaction);
            
            if ($response->isSuccessful()) {
                return response()->json([
                    'success' => true,
                    'hash'    => $response->getHash(),
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Transaction failed but no exception thrown.'], 400);

        } catch (\Soneso\StellarSDK\Exceptions\HorizonRequestException $e) {
            $errorMsg = 'Network error: ' . $e->getMessage();
            $horizonErr = $e->getHorizonErrorResponse();
            
            if ($horizonErr && $horizonErr->getExtras()) {
                $extras = $horizonErr->getExtras();
                $txCode = method_exists($extras, 'getResultCodesTransaction') ? $extras->getResultCodesTransaction() : 'Unknown';
                $opCodes = method_exists($extras, 'getResultCodesOperation') ? $extras->getResultCodesOperation() : [];
                
                $errorMsg = 'Horizon Rejected (Tx: ' . $txCode . ')';
                if (!empty($opCodes)) {
                     $errorMsg .= ' | Ops: ' . implode(', ', $opCodes);
                }
            }
            
            \Illuminate\Support\Facades\Log::error("Horizon Fail: " . $errorMsg);
            return response()->json(['success' => false, 'message' => $errorMsg], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Network error: ' . $e->getMessage()], 500);
        }
    }

    public function recordTip(Request $request)
    {
        $request->validate([
            'tx_hash'     => 'required|string|unique:tips,tx_hash',
            'amount'      => 'required|numeric',
            'asset'       => 'required|string',
            'receiver_id' => 'required|exists:users,id',
            'sender_key'  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $sender = User::where('public_key', $request->sender_key)->first();
            
            // Calculate YLX Rewards (according to whitepaper concept)
            // Let's say 1 XLM = 10 YLX bonus for creator and 5 YLX for fan
            $ylxBonusCreator = $request->amount * 10;
            $ylxRewardFan = $request->amount * 5;

            $tip = Tip::create([
                'sender_id'    => $sender ? $sender->id : null,
                'receiver_id'  => $request->receiver_id,
                'tx_hash'      => $request->tx_hash,
                'amount'       => $request->amount,
                'asset'        => $request->asset,
                'platform_fee' => $request->amount * 0.015,
                'bonus'        => $ylxBonusCreator,
                'status'       => 'confirmed',
            ]);

            // Distribute YLX Rewards (In a real app, this would be a real Stellar transaction from issuer)
            // For now, we record it in the database 'bonus' field.
            
            DB::commit();

            return response()->json(['success' => true, 'tip' => $tip]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
