<?php

namespace App\Services;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipService
{
    private StellarService $stellarService;
    private SorobanTipRegistryService $sorobanTipRegistry;

    public function __construct(
        StellarService $stellarService,
        SorobanTipRegistryService $sorobanTipRegistry
    )
    {
        $this->stellarService = $stellarService;
        $this->sorobanTipRegistry = $sorobanTipRegistry;
    }

    public function calculatePlatformFee(string $asset, string $amount): string
    {
        return $this->stellarService->calculateSplitAmounts($amount)['platform_fee'];
    }

    public function recordTipSecurely(array $data): array
    {
        if (empty($data['sender_key'])) {
            return ['success' => false, 'message' => 'Invalid sender wallet.'];
        }

        if (Tip::where('tx_hash', $data['tx_hash'])->exists()) {
            return ['success' => false, 'message' => 'This transaction hash has already been recorded.'];
        }

        $txVerify = $this->stellarService->verifyTransaction(
            $data['tx_hash'], 
            $data['receiver_public_key'],
            $data['amount'], 
            $data['asset'],
            $data['sender_key'] ?? null
        );

        if (!$txVerify['success']) {
            Log::channel('security')->warning('tip_verification_failed', [
                'tx_hash' => $data['tx_hash'],
                'receiver_id' => $data['receiver_id'] ?? null,
                'reason' => $txVerify['message'],
            ]);
            return ['success' => false, 'message' => $txVerify['message']];
        }

        DB::beginTransaction();
        try {
            if (Tip::where('tx_hash', $data['tx_hash'])->lockForUpdate()->exists()) {
                DB::rollBack();
                return ['success' => false, 'message' => 'This transaction hash has already been recorded.'];
            }

            $sender = null;
            if (!empty($data['sender_key'])) {
                $sender = User::where('public_key', $data['sender_key'])->first();
            }

            $receiver = User::findOrFail($data['receiver_id']);

            if (!empty($data['sender_key']) && $data['sender_key'] === $receiver->public_key) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Creators cannot tip their own link.'];
            }

            $platformFee = '0.0000000';
            $creatorReceives = $txVerify['creator_payout_amount'];

            $tip = Tip::create([
                'sender_id'             => $sender ? $sender->id : null,
                'receiver_id'           => $receiver->id,
                'tx_hash'               => $data['tx_hash'],
                'amount'                => $data['amount'],
                'asset'                 => $data['asset'],
                'asset_issuer'          => $txVerify['asset_issuer'] ?? null,
                'platform_fee'          => $platformFee,
                'network_fee'           => $txVerify['network_fee'] ?? 0,
                'bonus'                 => 0,
                'reward_ylx_amount'     => 0,
                'ylx_reward_status'     => 'not_available_current_mvp',
                'status'                => 'confirmed',
                'confirmed_at'          => now(),
                'sender_wallet'         => $txVerify['sender_wallet'] ?? $data['sender_key'] ?? null,
                'receiver_wallet'       => $txVerify['receiver_wallet'] ?? $receiver->public_key,
                'conversion_rate'       => null,
                'converted_ylx_amount'  => null,
                'creator_payout_amount' => $creatorReceives,
                'payout_status'         => 'completed',
                'payout_tx_hash'        => $data['tx_hash'],
                'stellar_meta'          => [
                    'ledger' => $txVerify['ledger'] ?? null,
                    'stellar_created_at' => $txVerify['created_at'] ?? null,
                    'network' => config('yolixa.network', 'testnet'),
                    'verification_model' => 'classic_xlm_direct_payment',
                ],
                'soroban_status'        => 'disabled',
                'message'               => $data['message'] ?? null,
                'is_anonymous'          => $data['is_anonymous'] ?? false,
                'sender_name'           => $data['sender_name'] ?? null,
            ]);

            DB::commit();

            if (config('yolixa.soroban.enabled') && filled(config('yolixa.soroban.tip_registry_contract_id'))) {
                $this->recordSorobanStatus($tip);
            }

            return ['success' => true, 'tip' => $tip, 'payout_status' => $tip->payout_status];

        } catch (QueryException $e) {
            DB::rollBack();
            if (str_contains(strtolower($e->getMessage()), 'unique')) {
                return ['success' => false, 'message' => 'This transaction hash has already been recorded.'];
            }

            Log::error('record_tip_query_failed', ['exception' => $e::class]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('record_tip_failed', ['exception' => $e::class]);
            return ['success' => false, 'message' => 'Internal Server Error while saving tip.'];
        }
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
