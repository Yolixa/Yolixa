<?php

namespace App\Services;

use App\Models\Tip;
use App\Models\TipIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class SorobanTipRouterService
{
    public function __construct(
        private StellarConfigurationService $stellarConfiguration,
        private ConversionService $conversionService,
        private SorobanTransactionVerifier $verifier,
        private XlmAmount $amounts
    ) {
    }

    public function executionMode(): string
    {
        return strtolower((string) config('yolixa.tip_execution_mode', 'classic'));
    }

    public function publicConfig(): array
    {
        return [
            'network' => $this->stellarConfiguration->networkName(),
            'networkPassphrase' => $this->stellarConfiguration->passphrase(),
            'rpcUrl' => config('yolixa.soroban.rpc_url'),
            'routerContractId' => config('yolixa.soroban.tip_router_contract_id'),
            'xlmTokenContractId' => config('yolixa.soroban.xlm_token_contract_id'),
            'executionMode' => $this->executionMode(),
            'explorerTxUrl' => $this->stellarConfiguration->explorerTxUrl(),
        ];
    }

    public function assertConfigured(): void
    {
        if ($this->executionMode() !== 'soroban') {
            throw new InvalidArgumentException('Soroban tipping is not the selected execution mode.');
        }

        if (!config('yolixa.soroban.enabled')) {
            throw new InvalidArgumentException('Soroban tipping is disabled.');
        }

        if ($this->stellarConfiguration->networkName() !== 'testnet') {
            throw new InvalidArgumentException('Phase 2 Soroban tipping only supports Stellar Testnet.');
        }

        if (!filter_var(config('yolixa.soroban.rpc_url'), FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Soroban RPC URL is not configured.');
        }

        foreach (['tip_router_contract_id', 'xlm_token_contract_id'] as $key) {
            $value = config("yolixa.soroban.{$key}");
            if (!$this->isValidContractId($value)) {
                throw new InvalidArgumentException("Soroban {$key} is not configured.");
            }
        }

        if (!$this->stellarConfiguration->isValidPublicKey(config('yolixa.platform_public_key'))) {
            throw new InvalidArgumentException('YOLIXA_PLATFORM_WALLET_PUBLIC must be configured with the expected treasury public address.');
        }
    }

    public function createIntent(User $fan, array $payload): TipIntent
    {
        $this->assertConfigured();

        $sender = strtoupper(trim((string) ($payload['sender'] ?? $fan->public_key)));
        if ($sender !== $fan->public_key) {
            throw new InvalidArgumentException('Connected wallet does not match the authenticated Yolixa session.');
        }

        if (!$this->stellarConfiguration->isValidPublicKey($sender)) {
            throw new InvalidArgumentException('Invalid sender wallet.');
        }

        $asset = strtoupper(trim((string) ($payload['asset'] ?? 'XLM')));
        if ($asset !== 'XLM') {
            throw new InvalidArgumentException('Phase 2 Soroban tipping supports XLM only.');
        }

        $creator = User::find((int) ($payload['receiver_id'] ?? 0));
        if (!$creator || $creator->role !== 'creator') {
            throw new InvalidArgumentException('Invalid creator.');
        }

        if (!$this->stellarConfiguration->isValidPublicKey($creator->public_key)) {
            throw new InvalidArgumentException('Creator wallet is invalid.');
        }

        if ($creator->public_key === $sender) {
            throw new InvalidArgumentException('Self tip blocked.');
        }

        $amountAtomic = $this->amounts->decimalToAtomic((string) ($payload['amount'] ?? ''));
        $this->amounts->validateWithinConfiguredLimits($amountAtomic);

        $tokenContractId = (string) config('yolixa.soroban.xlm_token_contract_id');
        $expiresAt = now()->addMinutes((int) config('yolixa.soroban.tip_intent_ttl_minutes', 30));

        $existing = TipIntent::query()
            ->where('sender_wallet', $sender)
            ->where('receiver_id', $creator->id)
            ->where('asset', 'XLM')
            ->where('token_contract_id', $tokenContractId)
            ->where('amount_atomic', $amountAtomic)
            ->whereIn('status', ['pending', 'submitted'])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($sender, $creator, $tokenContractId, $amountAtomic, $expiresAt) {
            $intent = TipIntent::create([
                'sender_wallet' => $sender,
                'receiver_id' => $creator->id,
                'receiver_wallet' => $creator->public_key,
                'asset' => 'XLM',
                'token_contract_id' => $tokenContractId,
                'amount' => $this->amounts->atomicToDecimal($amountAtomic),
                'amount_atomic' => $amountAtomic,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]);

            $intent->update(['contract_tip_id' => $intent->id]);

            return $intent->refresh();
        });
    }

    public function responseForIntent(TipIntent $intent): array
    {
        $split = $this->amounts->splitFee($intent->amount_atomic, (int) config('yolixa.soroban.fee_bps', 150));

        return [
            'success' => true,
            'intent_id' => $intent->id,
            'contract_tip_id' => (string) $intent->contract_tip_id,
            'sender' => $intent->sender_wallet,
            'creator' => $intent->receiver_wallet,
            'asset' => $intent->asset,
            'token_contract_id' => $intent->token_contract_id,
            'amount' => $this->amounts->atomicToDecimal($intent->amount_atomic),
            'amount_atomic' => $intent->amount_atomic,
            'creator_amount_atomic' => $split['creator_amount_atomic'],
            'platform_fee_atomic' => $split['platform_fee_atomic'],
            'creator_payout' => $split['creator_amount'],
            'platform_fee' => $split['platform_fee'],
            'router_contract_id' => config('yolixa.soroban.tip_router_contract_id'),
            'rpc_url' => config('yolixa.soroban.rpc_url'),
            'network' => $this->stellarConfiguration->networkName(),
            'network_passphrase' => $this->stellarConfiguration->passphrase(),
            'expires_at' => $intent->expires_at?->toIso8601String(),
        ];
    }

    public function confirm(User $fan, int $intentId, string $txHash): array
    {
        $this->submitted($fan, $intentId, $txHash);

        $intent = TipIntent::with('receiver')->findOrFail($intentId);

        if ($intent->status === 'confirmed') {
            $tip = Tip::where('tip_intent_id', $intent->id)->first();
            return ['success' => true, 'tip' => $tip, 'intent' => $intent, 'already_confirmed' => true];
        }

        if (!$intent->tx_hash && $intent->expires_at && $intent->expires_at->isPast()) {
            $intent->update(['status' => 'expired', 'failure_reason' => 'Intent expired before confirmation.']);
            throw new InvalidArgumentException('This tip intent expired. Please start a new Soroban tip.');
        }

        $verification = $this->verifier->verify($intent, (string) $intent->tx_hash);
        if (!($verification['success'] ?? false)) {
            $intent->update([
                'status' => ($verification['retryable'] ?? false) ? 'submitted' : 'failed',
                'failure_reason' => $verification['message'] ?? 'Soroban verification failed.',
            ]);

            return [
                'success' => false,
                'retryable' => (bool) ($verification['retryable'] ?? false),
                'message' => $verification['message'] ?? 'Soroban verification failed.',
                'intent' => $intent->refresh(),
            ];
        }

        return $this->recordConfirmedTip($intent->refresh(), $verification);
    }

    public function submitted(User $fan, int $intentId, string $txHash): array
    {
        $this->assertConfigured();

        $txHash = $this->normalizeTxHash($txHash);
        $expired = false;

        $result = DB::transaction(function () use ($fan, $intentId, $txHash, &$expired) {
            $intent = TipIntent::query()->lockForUpdate()->findOrFail($intentId);

            if ($intent->sender_wallet !== $fan->public_key) {
                throw new InvalidArgumentException('Connected wallet does not match this tip intent.');
            }

            $this->assertTxHashIsUsableForIntent($intent, $txHash);

            if ($intent->status === 'confirmed') {
                return [
                    'success' => true,
                    'intent' => $intent,
                    'already_confirmed' => true,
                    'submitted' => false,
                ];
            }

            if (!$intent->tx_hash && $intent->expires_at && $intent->expires_at->isPast()) {
                $intent->update(['status' => 'expired', 'failure_reason' => 'Intent expired before submission.']);
                $expired = true;

                return [
                    'success' => false,
                    'intent' => $intent->refresh(),
                    'expired' => true,
                    'submitted' => false,
                ];
            }

            if (!$intent->tx_hash) {
                $intent->update([
                    'status' => 'submitted',
                    'tx_hash' => $txHash,
                    'failure_reason' => null,
                ]);
            }

            return [
                'success' => true,
                'intent' => $intent->refresh(),
                'already_confirmed' => false,
                'submitted' => true,
            ];
        });

        if ($expired) {
            throw new InvalidArgumentException('This tip intent expired. Please start a new Soroban tip.');
        }

        return $result;
    }

    private function recordConfirmedTip(TipIntent $intent, array $verification): array
    {
        return DB::transaction(function () use ($intent, $verification) {
            $existing = Tip::where('tip_intent_id', $intent->id)->first();
            if ($existing) {
                $intent->update([
                    'status' => 'confirmed',
                    'confirmed_at' => $intent->confirmed_at ?? now(),
                    'failure_reason' => null,
                ]);

                return ['success' => true, 'tip' => $existing, 'intent' => $intent->refresh(), 'already_confirmed' => true];
            }

            $receiver = $intent->receiver;
            $sender = User::where('public_key', $intent->sender_wallet)->first();
            $amount = $this->amounts->atomicToDecimal($intent->amount_atomic);
            $networkFee = $this->amounts->atomicToDecimal((string) ($verification['network_fee_atomic'] ?? '0'));
            $conversion = $this->conversionService->convertToYlx('XLM', (float) $amount);
            $grossYlx = $conversion['converted_amount'];
            $reward = $this->calculateYlxReward($grossYlx);

            $tip = Tip::create([
                'tip_intent_id' => $intent->id,
                'sender_id' => $sender?->id,
                'receiver_id' => $receiver->id,
                'tx_hash' => $intent->tx_hash,
                'amount' => $amount,
                'asset' => 'XLM',
                'asset_issuer' => null,
                'platform_fee' => $verification['platform_fee'],
                'network_fee' => $networkFee,
                'bonus' => $reward,
                'reward_ylx_amount' => $reward,
                'ylx_reward_status' => $reward > 0 ? 'pending_claim' : 'not_configured',
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'sender_wallet' => $intent->sender_wallet,
                'receiver_wallet' => $intent->receiver_wallet,
                'conversion_rate' => $conversion['rate'],
                'converted_ylx_amount' => $grossYlx,
                'creator_payout_amount' => $verification['creator_amount'],
                'payout_status' => 'completed',
                'payout_tx_hash' => $intent->tx_hash,
                'stellar_meta' => [
                    'ledger' => $verification['ledger'] ?? null,
                    'network' => $this->stellarConfiguration->networkName(),
                    'router_contract_id' => $verification['router_contract_id'],
                    'token_contract_id' => $verification['token_contract_id'],
                    'contract_tip_id' => $verification['contract_tip_id'],
                    'receipt' => $verification['receipt'],
                    'creator_stats' => $verification['creator_stats'],
                ],
                'soroban_status' => 'confirmed',
                'soroban_tx_hash' => $intent->tx_hash,
                'soroban_recorded_at' => now(),
                'soroban_error' => null,
                'router_contract_id' => $verification['router_contract_id'],
                'token_contract_id' => $verification['token_contract_id'],
                'contract_tip_id' => $verification['contract_tip_id'],
            ]);

            if ($reward > 0) {
                $receiver->increment('ylx_claimable_balance', $reward);
            }

            $intent->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'failure_reason' => null,
            ]);

            return ['success' => true, 'tip' => $tip, 'intent' => $intent->refresh(), 'already_confirmed' => false];
        });
    }

    private function calculateYlxReward(float $grossYlx): float
    {
        $rate = (float) config('yolixa.ylx_reward_rate_percent', 0);
        if ($rate <= 0) {
            return 0;
        }

        return round($grossYlx * ($rate / 100), 7);
    }

    private function normalizeTxHash(string $txHash): string
    {
        $txHash = strtolower(trim($txHash));

        if (!preg_match('/^[a-f0-9]{64}$/', $txHash)) {
            throw new InvalidArgumentException('Invalid transaction hash.');
        }

        return $txHash;
    }

    private function assertTxHashIsUsableForIntent(TipIntent $intent, string $txHash): void
    {
        if ($intent->tx_hash && !hash_equals(strtolower((string) $intent->tx_hash), $txHash)) {
            throw new InvalidArgumentException('A different Soroban transaction hash is already submitted for this tip intent.');
        }

        $usedByAnotherIntent = TipIntent::query()
            ->whereRaw('LOWER(tx_hash) = ?', [$txHash])
            ->where('id', '!=', $intent->id)
            ->exists();

        if ($usedByAnotherIntent) {
            throw new InvalidArgumentException('This Soroban transaction hash is already attached to another tip intent.');
        }
    }

    private function isValidContractId(?string $contractId): bool
    {
        if (!is_string($contractId) || !str_starts_with($contractId, 'C')) {
            return false;
        }

        try {
            StrKey::decodeContractId($contractId);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
