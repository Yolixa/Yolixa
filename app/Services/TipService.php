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
    private SorobanTipRegistryService $sorobanTipRegistry;

    public function __construct(
        StellarService $stellarService,
        ConversionService $conversionService,
        SorobanTipRegistryService $sorobanTipRegistry
    )
    {
        $this->stellarService = $stellarService;
        $this->conversionService = $conversionService;
        $this->sorobanTipRegistry = $sorobanTipRegistry;
    }

    public function calculatePlatformFee(string $asset, float $amount): float
    {
        return (float) $this->stellarService->calculateSplitAmounts((string) $amount)['platform_fee'];
    }

    public function recordTipSecurely(array $data): array
    {
        if (empty($data['sender_key'])) {
            return ['success' => false, 'message' => 'Invalid sender wallet.'];
        }

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
            $platformFee = $txVerify['platform_fee'];
            $creatorReceives = $txVerify['creator_payout_amount'];
            $reward = $this->calculateYlxReward($grossYlx);
            $rewardStatus = $reward > 0 ? 'pending_claim' : 'not_configured';

            $tip = Tip::create([
                'sender_id'             => $sender ? $sender->id : null,
                'receiver_id'           => $receiver->id,
                'tx_hash'               => $data['tx_hash'],
                'amount'                => $data['amount'],
                'asset'                 => $data['asset'],
                'asset_issuer'          => $txVerify['asset_issuer'] ?? null,
                'platform_fee'          => $platformFee,
                'network_fee'           => $txVerify['network_fee'] ?? 0,
                'bonus'                 => $reward,
                'reward_ylx_amount'     => $reward,
                'ylx_reward_status'     => $rewardStatus,
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
                    'network' => config('yolixa.network', 'testnet'),
                    'platform_wallet' => $txVerify['platform_wallet'] ?? null,
                ],
                'soroban_status'        => config('yolixa.soroban.enabled') ? 'pending' : 'disabled',
                'message'               => $data['message'] ?? null,
                'is_anonymous'          => $data['is_anonymous'] ?? false,
                'sender_name'           => $data['sender_name'] ?? null,
            ]);

            if ($reward > 0) {
                $receiver->increment('ylx_claimable_balance', $reward);
            }

            DB::commit();

            $this->recordSorobanStatus($tip);

            return ['success' => true, 'tip' => $tip, 'payout_status' => $tip->payout_status];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Record Tip Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        }
    }

    private function calculateYlxReward(float $grossYlx): float
    {
        $rate = (float) config('yolixa.ylx_reward_rate_percent', 0);
        if ($rate <= 0) {
            return 0;
        }

        return round($grossYlx * ($rate / 100), 7);
    }

    private function recordSorobanStatus(Tip $tip): void
    {
        $result = $this->sorobanTipRegistry->recordTip($tip);

        if (($result['status'] ?? null) === 'disabled') {
            $tip->update(['soroban_status' => 'disabled']);
            return;
        }

        if ($result['success'] ?? false) {
            $tip->update([
                'soroban_status' => 'confirmed',
                'soroban_tx_hash' => $result['tx_hash'] ?? null,
                'soroban_recorded_at' => now(),
                'soroban_error' => null,
            ]);
            return;
        }

        $tip->update([
            'soroban_status' => $result['status'] ?? 'failed',
            'soroban_error' => $result['message'] ?? 'Soroban registry failed.',
        ]);
    }
}
