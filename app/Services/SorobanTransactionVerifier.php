<?php

namespace App\Services;

use App\Models\TipIntent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Soroban\Address as SorobanAddress;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionEvent;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Throwable;

class SorobanTransactionVerifier
{
    public function __construct(
        private JsonSorobanRpcClient $rpc,
        private XlmAmount $amounts
    ) {}

    public function verify(TipIntent $intent, string $txHash): array
    {
        try {
            $tx = $this->rpc->getTransaction($txHash);
            $status = strtoupper((string) ($tx['status'] ?? ''));

            if (in_array($status, ['NOT_FOUND', 'PENDING'], true)) {
                return [
                    'success' => false,
                    'retryable' => true,
                    'status' => strtolower($status),
                    'message' => 'Soroban transaction is not final yet. Confirmation can be retried safely.',
                ];
            }

            if ($status !== 'SUCCESS') {
                return ['success' => false, 'message' => 'Soroban transaction did not complete successfully.'];
            }

            $evidence = [
                'transaction_success' => true,
                'ledger' => $tx['ledger'] ?? $tx['ledgerNumber'] ?? null,
                'network_fee_atomic' => (string) ($tx['feeCharged'] ?? $tx['fee_charged'] ?? 0),
            ];

            $envelopeXdr = $tx['envelopeXdr'] ?? $tx['envelope_xdr'] ?? null;
            if (is_string($envelopeXdr) && $envelopeXdr !== '') {
                $evidence = array_merge($evidence, $this->parseInvocationEvidence($envelopeXdr));
            }

            $metaXdr = $tx['resultMetaXdr'] ?? $tx['result_meta_xdr'] ?? null;
            if (is_string($metaXdr) && $metaXdr !== '') {
                $evidence = array_merge($evidence, $this->parseEventEvidence($metaXdr));
            }

            $evidence = array_merge($evidence, $this->readRouterEvidence($intent));

            return $this->verifyEvidence($intent, $evidence);
        } catch (Throwable $e) {
            $retryable = $this->isRetryableRpcFailure($e);

            Log::channel('security')->warning('Soroban tip verification failed: '.$e->getMessage(), [
                'intent_id' => $intent->id,
                'tx_hash' => $txHash,
                'retryable' => $retryable,
            ]);

            if ($retryable) {
                return [
                    'success' => false,
                    'retryable' => true,
                    'message' => 'Soroban RPC is temporarily unavailable. Confirmation can be retried safely.',
                ];
            }

            return ['success' => false, 'message' => 'Could not verify Soroban transaction proof.'];
        }
    }

    public function verifyEvidence(TipIntent $intent, array $evidence): array
    {
        if (($evidence['transaction_success'] ?? false) !== true) {
            return ['success' => false, 'message' => 'Soroban transaction was not successful.'];
        }

        $expectedRouter = (string) config('yolixa.soroban.tip_router_contract_id');
        $expectedToken = (string) config('yolixa.soroban.xlm_token_contract_id');
        $expectedTreasury = $this->expectedTreasuryPublicKey();
        if ($expectedTreasury === null) {
            return ['success' => false, 'message' => 'Expected Yolixa treasury public address is not configured.'];
        }

        $expectedFee = $this->amounts->splitFee($intent->amount_atomic, (int) config('yolixa.soroban.fee_bps', 150));
        $expected = [
            'router_contract_id' => $expectedRouter,
            'function' => 'tip',
            'sender' => $intent->sender_wallet,
            'creator' => $intent->receiver_wallet,
            'token_contract_id' => $expectedToken,
            'amount_atomic' => $intent->amount_atomic,
            'contract_tip_id' => (string) $intent->contract_tip_id,
        ];

        foreach ($expected as $key => $value) {
            if ((string) ($evidence[$key] ?? '') !== (string) $value) {
                return ['success' => false, 'message' => "Soroban transaction {$key} does not match the tip intent."];
            }
        }

        if ((string) $intent->token_contract_id !== $expectedToken) {
            return ['success' => false, 'message' => 'Tip intent token is not the configured XLM SAC.'];
        }

        $routerTreasury = $evidence['router_config']['treasury'] ?? null;
        if (!$this->isValidPublicKey($routerTreasury)) {
            return ['success' => false, 'message' => 'Router treasury proof was not found.'];
        }

        if ($routerTreasury !== $expectedTreasury) {
            return ['success' => false, 'message' => 'Router treasury does not match Yolixa config.'];
        }

        if (($evidence['tip_exists'] ?? null) !== true) {
            return ['success' => false, 'message' => 'Router tip_exists proof was not found.'];
        }

        if (($evidence['router_config']['fee_bps'] ?? null) !== null
            && (string) $evidence['router_config']['fee_bps'] !== (string) config('yolixa.soroban.fee_bps', 150)
        ) {
            return ['success' => false, 'message' => 'Router fee configuration does not match Yolixa config.'];
        }

        if (($evidence['router_config']['xlm_enabled'] ?? null) !== null
            && $evidence['router_config']['xlm_enabled'] !== true
        ) {
            return ['success' => false, 'message' => 'Configured XLM SAC is not enabled on the router.'];
        }

        if (($evidence['router_config']['paused'] ?? null) !== null
            && $evidence['router_config']['paused'] !== false
        ) {
            return ['success' => false, 'message' => 'Router is paused.'];
        }

        if (($evidence['tip_event'] ?? null) !== null && ! is_array($evidence['tip_event'])) {
            return ['success' => false, 'message' => 'Router tip_event proof is malformed.'];
        }

        foreach (array_filter(['tip_event', 'receipt'], fn ($proofKey) => isset($evidence[$proofKey])) as $proofKey) {
            $proof = $evidence[$proofKey] ?? null;
            if (! is_array($proof)) {
                return ['success' => false, 'message' => "Router {$proofKey} proof was not found."];
            }

            foreach ([
                'sender' => $intent->sender_wallet,
                'creator' => $intent->receiver_wallet,
                'token_contract_id' => $intent->token_contract_id,
                'gross_amount' => $intent->amount_atomic,
                'creator_amount' => $expectedFee['creator_amount_atomic'],
                'platform_fee' => $expectedFee['platform_fee_atomic'],
                'contract_tip_id' => (string) $intent->contract_tip_id,
            ] as $key => $value) {
                if ((string) ($proof[$key] ?? '') !== (string) $value) {
                    return ['success' => false, 'message' => "Router {$proofKey} {$key} does not match the tip intent."];
                }
            }
        }

        if (! isset($evidence['receipt'])) {
            return ['success' => false, 'message' => 'Router receipt proof was not found.'];
        }

        $stats = $evidence['creator_stats'] ?? null;
        if (! is_array($stats) || (int) ($stats['tip_count'] ?? 0) < 1) {
            return ['success' => false, 'message' => 'Creator stats proof was not found on-chain.'];
        }

        if ($this->amounts->compareAtomic((string) ($stats['gross_received'] ?? '0'), $intent->amount_atomic) < 0) {
            return ['success' => false, 'message' => 'Creator gross stats do not include this tip.'];
        }

        if ($this->amounts->compareAtomic((string) ($stats['net_received'] ?? '0'), $expectedFee['creator_amount_atomic']) < 0) {
            return ['success' => false, 'message' => 'Creator net stats do not include this tip.'];
        }

        return [
            'success' => true,
            'ledger' => $evidence['ledger'] ?? null,
            'network_fee_atomic' => (string) ($evidence['network_fee_atomic'] ?? '0'),
            'creator_amount_atomic' => $expectedFee['creator_amount_atomic'],
            'platform_fee_atomic' => $expectedFee['platform_fee_atomic'],
            'creator_amount' => $expectedFee['creator_amount'],
            'platform_fee' => $expectedFee['platform_fee'],
            'router_contract_id' => $expectedRouter,
            'token_contract_id' => $expectedToken,
            'contract_tip_id' => (string) $intent->contract_tip_id,
            'receipt' => $evidence['receipt'],
            'creator_stats' => $stats,
            'tip_exists' => true,
            'router_config' => $evidence['router_config'] ?? [],
        ];
    }

    private function isRetryableRpcFailure(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof SorobanRpcException) {
            return $e->retryable();
        }

        $code = (int) $e->getCode();
        if ($code === 429 || ($code >= 500 && $code <= 599)) {
            return true;
        }

        $message = strtolower($e->getMessage());
        foreach ([
            'timed out',
            'timeout',
            'connection refused',
            'connection reset',
            'connection aborted',
            'could not connect',
            'failed to connect',
            'could not resolve host',
            'temporary failure',
            'temporarily unavailable',
            'service unavailable',
            'too many requests',
            'rate limit',
            'http 429',
            'http 500',
            'http 502',
            'http 503',
            'http 504',
            'rpc is unavailable',
            'rpc unavailable',
            'curl error 6',
            'curl error 7',
            'curl error 28',
            'simulation request failed',
            'simulate transaction request failed',
            'read simulation failed',
            'read-only simulation failed',
        ] as $retryableNeedle) {
            if (str_contains($message, $retryableNeedle)) {
                return true;
            }
        }

        return $e->getPrevious() ? $this->isRetryableRpcFailure($e->getPrevious()) : false;
    }

    private function expectedTreasuryPublicKey(): ?string
    {
        $treasury = (string) config('yolixa.platform_public_key', '');

        return $this->isValidPublicKey($treasury) ? $treasury : null;
    }

    private function isValidPublicKey(mixed $publicKey): bool
    {
        if (!is_string($publicKey) || !str_starts_with($publicKey, 'G') || strlen($publicKey) !== 56) {
            return false;
        }

        try {
            KeyPair::fromAccountId($publicKey);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function readRouterEvidence(TipIntent $intent): array
    {
        $sourceAccount = (string) config('yolixa.soroban.verifier_source_account', '') ?: $intent->sender_wallet;
        $sender = $this->rpc->addressArgument($intent->sender_wallet);
        $creator = $this->rpc->addressArgument($intent->receiver_wallet);
        $token = $this->rpc->addressArgument((string) config('yolixa.soroban.xlm_token_contract_id'));
        $tipId = $this->rpc->u64Argument((string) $intent->contract_tip_id);

        $tipExists = $this->rpc->callContractRead('tip_exists', [$sender, $tipId], $sourceAccount);
        $receipt = $this->rpc->callContractRead('get_tip', [$sender, $tipId], $sourceAccount);
        $creatorStats = $this->rpc->callContractRead('get_creator_stats', [$creator], $sourceAccount);

        return [
            'tip_exists' => $tipExists === true,
            'receipt' => $this->normalizeReceipt($receipt),
            'creator_stats' => $this->normalizeCreatorStats($creatorStats),
            'router_config' => [
                'fee_bps' => $this->rpc->callContractRead('get_fee_bps', [], $sourceAccount),
                'treasury' => $this->rpc->callContractRead('get_treasury', [], $sourceAccount),
                'paused' => $this->rpc->callContractRead('is_paused', [], $sourceAccount),
                'xlm_enabled' => $this->rpc->callContractRead('is_token_enabled', [$token], $sourceAccount),
            ],
        ];
    }

    private function normalizeReceipt(mixed $receipt): array
    {
        if (! is_array($receipt)) {
            return [];
        }

        return [
            'contract_tip_id' => (string) ($receipt['tip_id'] ?? $receipt['contract_tip_id'] ?? ''),
            'sender' => (string) ($receipt['sender'] ?? ''),
            'creator' => (string) ($receipt['creator'] ?? ''),
            'token_contract_id' => (string) ($receipt['token'] ?? $receipt['token_contract_id'] ?? ''),
            'gross_amount' => (string) ($receipt['gross_amount'] ?? ''),
            'creator_amount' => (string) ($receipt['creator_amount'] ?? ''),
            'platform_fee' => (string) ($receipt['platform_fee'] ?? ''),
        ];
    }

    private function normalizeCreatorStats(mixed $stats): array
    {
        if (! is_array($stats)) {
            return [];
        }

        return [
            'tip_count' => (string) ($stats['tip_count'] ?? '0'),
            'gross_received' => (string) ($stats['gross_received'] ?? '0'),
            'net_received' => (string) ($stats['net_received'] ?? '0'),
        ];
    }

    private function parseInvocationEvidence(string $envelopeXdr): array
    {
        $abstract = AbstractTransaction::fromEnvelopeBase64XdrString($envelopeXdr);
        $transaction = $abstract instanceof Transaction ? $abstract : null;

        if (! $transaction) {
            return [];
        }

        foreach ($transaction->getOperations() as $operation) {
            if (! $operation instanceof InvokeHostFunctionOperation) {
                continue;
            }

            $function = $operation->getFunction();
            if (! $function instanceof InvokeContractHostFunction || $function->getFunctionName() !== 'tip') {
                continue;
            }

            $arguments = $function->getArguments() ?? [];
            if (count($arguments) !== 5) {
                continue;
            }

            return [
                'router_contract_id' => $this->normalizeContractId($function->getContractId()),
                'function' => $function->getFunctionName(),
                'sender' => $this->scValToAddress($arguments[0]),
                'creator' => $this->scValToAddress($arguments[1]),
                'token_contract_id' => $this->scValToAddress($arguments[2]),
                'amount_atomic' => $this->scValToIntegerString($arguments[3]),
                'contract_tip_id' => $this->scValToIntegerString($arguments[4]),
            ];
        }

        return [];
    }

    private function parseEventEvidence(string $metaXdr): array
    {
        $meta = XdrTransactionMeta::fromBase64Xdr($metaXdr);
        $evidence = [];

        foreach ($this->contractEventsFromMeta($meta) as $event) {
            $eventProof = $this->tipEventProof($event);
            if ($eventProof !== []) {
                $evidence['tip_event'] = $eventProof;
            }
        }

        return $evidence;
    }

    private function contractEventsFromMeta(XdrTransactionMeta $meta): array
    {
        $events = [];

        foreach ($meta->getV3()?->getSorobanMeta()?->getEvents() ?? [] as $event) {
            $events[] = $event;
        }

        foreach ($meta->getV4()?->getEvents() ?? [] as $transactionEvent) {
            if ($transactionEvent instanceof XdrTransactionEvent) {
                $events[] = $transactionEvent->getEvent();
            }
        }

        foreach ($meta->getV4()?->getSorobanMeta()?->getEvents() ?? [] as $event) {
            $events[] = $event;
        }

        return $events;
    }

    private function tipEventProof(XdrContractEvent $event): array
    {
        $contractId = $event->hash ? StrKey::encodeContractId($event->hash) : null;
        if ($contractId !== config('yolixa.soroban.tip_router_contract_id')) {
            return [];
        }

        $body = $event->body->v0;
        if (! $body) {
            return [];
        }

        $topics = array_map(fn (XdrSCVal $topic) => $this->scValToNative($topic), $body->getTopics());
        if (($topics[0] ?? null) !== 'tip') {
            return [];
        }

        $data = $this->scValToMap($body->getData());

        return [
            'contract_tip_id' => (string) ($topics[1] ?? ''),
            'sender' => (string) ($topics[2] ?? ''),
            'creator' => (string) ($topics[3] ?? ''),
            'token_contract_id' => (string) ($data['token'] ?? ''),
            'gross_amount' => (string) ($data['gross_amount'] ?? ''),
            'creator_amount' => (string) ($data['creator_amount'] ?? ''),
            'platform_fee' => (string) ($data['platform_fee'] ?? ''),
        ];
    }

    private function scValToNative(XdrSCVal $value): mixed
    {
        if ($value->getSym() !== null) {
            return $value->getSym();
        }

        if ($value->getStr() !== null) {
            return $value->getStr();
        }

        if ($value->getB() !== null) {
            return $value->getB();
        }

        if ($value->getU32() !== null) {
            return $value->getU32();
        }

        if ($value->getU64() !== null || $value->getI64() !== null || $value->getU128() !== null || $value->getI128() !== null) {
            return $this->scValToIntegerString($value);
        }

        if ($value->getAddress() !== null) {
            return $this->scValToAddress($value);
        }

        if ($value->getVec() !== null) {
            return array_map(fn (XdrSCVal $item) => $this->scValToNative($item), $value->getVec());
        }

        if ($value->getMap() !== null) {
            return $this->scValToMap($value);
        }

        return null;
    }

    private function scValToMap(?XdrSCVal $value): array
    {
        if (! $value || $value->getMap() === null) {
            return [];
        }

        $map = [];
        foreach ($value->getMap() as $entry) {
            $key = $this->scValToNative($entry->getKey());
            if (is_string($key)) {
                $map[$key] = $this->scValToNative($entry->getVal());
            }
        }

        return $map;
    }

    private function scValToAddress(XdrSCVal $value): ?string
    {
        if ($value->getAddress() === null) {
            return null;
        }

        return $this->scAddressToString($value->getAddress());
    }

    private function scAddressToString($address): ?string
    {
        $decoded = SorobanAddress::fromXdr($address);

        if ($decoded->getAccountId() !== null) {
            return $decoded->getAccountId();
        }

        if ($decoded->getContractId() !== null) {
            return StrKey::encodeContractIdHex($decoded->getContractId());
        }

        return null;
    }

    private function normalizeContractId(string $contractId): string
    {
        if (str_starts_with($contractId, 'C')) {
            StrKey::decodeContractId($contractId);

            return $contractId;
        }

        return StrKey::encodeContractIdHex($contractId);
    }

    private function scValToIntegerString(XdrSCVal $value): string
    {
        if ($value->getU64() !== null) {
            return (string) $value->getU64();
        }

        if ($value->getI64() !== null) {
            return (string) $value->getI64();
        }

        if ($value->getU32() !== null) {
            return (string) $value->getU32();
        }

        if ($value->getI128() !== null) {
            $parts = $value->getI128();
            if ($parts->getHi() === 0 && $parts->getLo() >= 0) {
                return (string) $parts->getLo();
            }
        }

        if ($value->getU128() !== null) {
            $parts = $value->getU128();
            if ($parts->getHi() === 0 && $parts->getLo() >= 0) {
                return (string) $parts->getLo();
            }
        }

        $bigInt = $value->toBigInt();

        return $bigInt !== null ? gmp_strval($bigInt) : '';
    }
}
