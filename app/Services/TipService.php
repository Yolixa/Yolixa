<?php

namespace App\Services;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipService
{
    private StellarService $stellarService;

    public function __construct(StellarService $stellarService)
    {
        $this->stellarService = $stellarService;
    }

    /**
     * Calculates required platform fee dynamically based on asset.
     */
    public function calculatePlatformFee(string $asset, float $amount): float
    {
        if ($asset === 'YLX') {
            return 0; // 0% fee for YLX natively
        }
        
        $percentage = config('yolixa.fee_percentage', 0.015); // Fallback to 1.5%
        return floatval($amount) * $percentage;
    }

    /**
     * Handles recording a tip into the database securely.
     */
    public function recordTipSecurely(array $data): array
    {
        // 1. Validate hash strongly first against Horizon
        $txVerify = $this->stellarService->verifyTransaction(
            $data['tx_hash'], 
            $data['receiver_public_key'], 
            $data['amount'] - $data['platform_fee'], // The creator gets the amount minus the platform fee
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
            
            // Calculate YLX Rewards
            // E.g., 1 XLM = 10 YLX for creator
            $ylxBonusCreator = $data['asset'] === 'XLM' ? $data['amount'] * 10 : 0;

            $tip = Tip::create([
                'sender_id'    => $sender ? $sender->id : null,
                'receiver_id'  => $receiver->id,
                'tx_hash'      => $data['tx_hash'],
                'amount'       => $data['amount'],
                'asset'        => $data['asset'],
                'platform_fee' => $data['platform_fee'],
                'bonus'        => $ylxBonusCreator,
                'status'       => 'confirmed',
                'message'      => $data['message'] ?? null,
                'is_anonymous' => $data['is_anonymous'] ?? false,
                'sender_name'  => $data['sender_name'] ?? null,
            ]);

            // Accumulate rewards to creator profile
            if ($ylxBonusCreator > 0) {
                $receiver->increment('ylx_claimable_balance', $ylxBonusCreator);
            }
            
            DB::commit();

            return ['success' => true, 'tip' => $tip];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Record Tip Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        }
    }
}
