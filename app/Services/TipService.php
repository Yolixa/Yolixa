<?php

namespace App\Services;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipService
{
    private StellarService $stellarService;
    private ConversionService $conversionService;

    public function __construct(StellarService $stellarService, ConversionService $conversionService)
    {
        $this->stellarService = $stellarService;
        $this->conversionService = $conversionService;
    }

    /**
     * Calculates required platform fee dynamically based on asset.
     */
    public function calculatePlatformFee(string $asset, float $amount): float
    {
        // This calculates fee on the gross YLX. Wait, the previous logic calculated it on the input asset.
        // Let's keep it calculating on the input asset for `buildXdr` compatibility if needed,
        // or just use 0 since the user pays the TOTAL amount to the platform, and the backend handles the fee split logically.
        // Actually, the user shouldn't pay a fee ON TOP of the tip if we want an inclusive fee.
        // Let's assume inclusive fee: User tips 10 XLM. Platform takes 5% of 10 XLM (0.5 XLM). Creator gets 9.5 XLM worth of YLX.
        // For simplicity, let's keep the existing interface but return 0 here because the tip is sent to the pool pool entirely.
        
        return 0; // Fee is handled logically during payout conversion.
    }

    /**
     * Handles recording a tip into the database securely and triggering the payout.
     */
    public function recordTipSecurely(array $data): array
    {
        $platformWallet = config('yolixa.platform_collection_wallet');

        // 1. Validate hash strongly first against Horizon:
        // Ensures the User sent the entire $data['amount'] of $data['asset'] to the Platform Pool
        $txVerify = $this->stellarService->verifyTransaction(
            $data['tx_hash'], 
            $platformWallet, 
            $data['amount'], 
            $data['asset']
        );

        if (!$txVerify['success']) {
            Log::channel('security')->warning('Failed TIP verification attempt. TX Hash: ' . $data['tx_hash'] . ' Reason: ' . $txVerify['message']);
            return ['success' => false, 'message' => $txVerify['message']];
        }

        DB::beginTransaction();
        try {
            $sender = null;
            if (!empty($data['sender_key'])) {
                $sender = User::where('public_key', $data['sender_key'])->first();
            }

            $receiver = User::findOrFail($data['receiver_id']);
            
            // Calculate Conversions
            $conversion = $this->conversionService->convertToYlx($data['asset'], $data['amount']);
            $grossYlx = $conversion['converted_amount'];
            $fees = $this->conversionService->calculateFees($grossYlx, $data['asset']);
            
            $netCreatorYlx = $fees['net_payout'];

            $tip = Tip::create([
                'sender_id'             => $sender ? $sender->id : null,
                'receiver_id'           => $receiver->id,
                'tx_hash'               => $data['tx_hash'],
                
                // Original Tip Info
                'amount'                => $data['amount'],
                'asset'                 => $data['asset'],
                'platform_fee'          => $fees['fee_amount'], // Storing fee value (YLX equivalent)
                'bonus'                 => 0, // Deprecated via new conversion flow
                'status'                => 'confirmed', // Input transaction is confirmed
                
                // Conversion Info
                'conversion_rate'       => $conversion['rate'],
                'converted_ylx_amount'  => $grossYlx,
                'creator_payout_amount' => $netCreatorYlx,
                'payout_status'         => 'pending',
                
                'message'               => $data['message'] ?? null,
                'is_anonymous'          => $data['is_anonymous'] ?? false,
                'sender_name'           => $data['sender_name'] ?? null,
            ]);

            DB::commit();

            // 2. Trigger Payout
            $payoutResult = $this->stellarService->payoutCreatorInYlx($receiver->public_key, $netCreatorYlx);

            if ($payoutResult['success']) {
                $tip->update([
                    'payout_status'  => 'completed',
                    'payout_tx_hash' => $payoutResult['hash']
                ]);
            } else {
                $tip->update([
                    'payout_status' => 'failed',
                    'payout_error'  => $payoutResult['message']
                ]);
                Log::error('Tip Payout Failed for Tip ID ' . $tip->id . ': ' . $payoutResult['message']);
            }

            return ['success' => true, 'tip' => $tip, 'payout_status' => $tip->payout_status];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Record Tip Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        }
    }
}
