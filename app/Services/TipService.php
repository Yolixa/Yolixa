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

    public function calculatePlatformFee(string $asset, float $amount): float
    {
        if ($asset !== 'XLM') {
            return 0;
        }

        return round($amount * (float) config('yolixa.fee_percentage', 0.015), 7);
    }

    public function recordTipSecurely(array $data): array
    {
        $txVerify = $this->stellarService->verifyTransaction(
            $data['tx_hash'], 
            $data['receiver_public_key'],
            $data['amount'], 
            $data['asset'],
            $data['sender_key'] ?? null
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

            if (!empty($data['sender_key']) && $data['sender_key'] === $receiver->public_key) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Creators cannot tip their own link.'];
            }

            $conversion = $this->conversionService->convertToYlx($data['asset'], $data['amount']);
            $grossYlx = $conversion['converted_amount'];
            $platformFee = $this->calculatePlatformFee($data['asset'], $data['amount']);
            $creatorReceives = max($data['amount'] - $platformFee, 0);

            $tip = Tip::create([
                'sender_id'             => $sender ? $sender->id : null,
                'receiver_id'           => $receiver->id,
                'tx_hash'               => $data['tx_hash'],
                'amount'                => $data['amount'],
                'asset'                 => $data['asset'],
                'platform_fee'          => $platformFee,
                'network_fee'           => $txVerify['network_fee'] ?? 0,
                'bonus'                 => 0,
                'status'                => 'confirmed',
                'confirmed_at'          => now(),
                'sender_wallet'         => $txVerify['sender_wallet'] ?? $data['sender_key'] ?? null,
                'receiver_wallet'       => $txVerify['receiver_wallet'] ?? $receiver->public_key,
                'conversion_rate'       => $conversion['rate'],
                'converted_ylx_amount'  => $grossYlx,
                'creator_payout_amount' => $creatorReceives,
                'payout_status'         => 'completed',
                'payout_tx_hash'        => $data['tx_hash'],
                'stellar_meta'          => [
                    'ledger' => $txVerify['ledger'] ?? null,
                    'stellar_created_at' => $txVerify['created_at'] ?? null,
                    'network' => 'testnet',
                ],
                'message'               => $data['message'] ?? null,
                'is_anonymous'          => $data['is_anonymous'] ?? false,
                'sender_name'           => $data['sender_name'] ?? null,
            ]);

            DB::commit();

            return ['success' => true, 'tip' => $tip, 'payout_status' => $tip->payout_status];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Record Tip Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        }
    }
}
