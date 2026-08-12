<?php

namespace App\Services;

use App\Models\TipIntent;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Soroban\Address as SorobanAddress;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Throwable;

class SorobanTransactionVerifier
{
    public function __construct(
        private JsonSorobanRpcClient $rpc,
        private XlmAmount $amounts
    ) {
    }

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
                $evidence = array_merge($evidence, $this->parseMetaEvidence($metaXdr, $intent));
            }

            return $this->verifyEvidence($intent, $evidence);
        } catch (Throwable $e) {
            Log::channel('security')->warning('Soroban tip verification failed: ' . $e->getMessage(), [
                'intent_id' => $intent->id,
                'tx_hash' => $txHash,
            ]);

            return ['success' => false, 'message' => 'Could not verify Soroban transaction proof.'];
        }
    }

    public function verifyEvidence(TipIntent $intent, array $evidence): array
    {
        if (($evidence['transaction_success'] ?? false) !== true) {
            return ['success' => false, 'message' => 'Soroban transaction was not successful.'];
        }

        $expectedRouter = (string) config('yolixa.soroban.tip_router_contract_id');
        $expectedFee = $this->amounts->splitFee($intent->amount_atomic, (int) config('yolixa.soroban.fee_bps', 150));
        $expected = [
            'router_contract_id' => $expectedRouter,
            'function' => 'tip',
            'sender' => $intent->sender_wallet,
            'creator' => $intent->receiver_wallet,
            'token_contract_id' => $intent->token_contract_id,
            'amount_atomic' => $intent->amount_atomic,
            'contract_tip_id' => (string) $intent->contract_tip_id,
        ];

        foreach ($expected as $key => $value) {
            if ((string) ($evidence[$key] ?? '') !== (string) $value) {
                return ['success' => false, 'message' => "Soroban transaction {$key} does not match the tip intent."];
            }
        }

        foreach (['tip_event', 'receipt'] as $proofKey) {
            $proof = $evidence[$proofKey] ?? null;
            if (!is_array($proof)) {
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

        $stats = $evidence['creator_stats'] ?? null;
        if (!is_array($stats) || (int) ($stats['tip_count'] ?? 0) < 1) {
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
            'token_contract_id' => $intent->token_contract_id,
            'contract_tip_id' => (string) $intent->contract_tip_id,
            'receipt' => $evidence['receipt'],
            'creator_stats' => $stats,
        ];
    }

    private function parseInvocationEvidence(string $envelopeXdr): array
    {
        $abstract = AbstractTransaction::fromEnvelopeBase64XdrString($envelopeXdr);
        $transaction = $abstract instanceof Transaction ? $abstract : null;

        if (!$transaction) {
            return [];
        }

        foreach ($transaction->getOperations() as $operation) {
            if (!$operation instanceof InvokeHostFunctionOperation) {
                continue;
            }

            $function = $operation->getFunction();
            if (!$function instanceof InvokeContractHostFunction || $function->getFunctionName() !== 'tip') {
                continue;
            }

            $arguments = $function->getArguments() ?? [];
            if (count($arguments) !== 5) {
                continue;
            }

            return [
                'router_contract_id' => StrKey::encodeContractIdHex($function->getContractId()),
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

    private function parseMetaEvidence(string $metaXdr, TipIntent $intent): array
    {
        $meta = XdrTransactionMeta::fromBase64Xdr($metaXdr);
        $sorobanMeta = $meta->getV3()?->getSorobanMeta();
        $evidence = [];

        if (!$sorobanMeta) {
            return $evidence;
        }

        foreach ($sorobanMeta->getEvents() as $event) {
            $contractId = $event->hash ? StrKey::encodeContractId($event->hash) : null;
            if ($contractId !== config('yolixa.soroban.tip_router_contract_id')) {
                continue;
            }

            $body = $event->body->v0;
            if (!$body) {
                continue;
            }

            $topics = array_map(fn (XdrSCVal $topic) => $this->scValToNative($topic), $body->getTopics());
            if (($topics[0] ?? null) !== 'tip') {
                continue;
            }

            $data = $this->scValToMap($body->getData());
            $evidence['tip_event'] = [
                'contract_tip_id' => (string) ($topics[1] ?? ''),
                'sender' => (string) ($topics[2] ?? ''),
                'creator' => (string) ($topics[3] ?? ''),
                'token_contract_id' => (string) ($data['token'] ?? ''),
                'gross_amount' => (string) ($data['gross_amount'] ?? ''),
                'creator_amount' => (string) ($data['creator_amount'] ?? ''),
                'platform_fee' => (string) ($data['platform_fee'] ?? ''),
            ];
        }

        foreach ($meta->getV3()->getTxChangesAfter() as $change) {
            $ledgerEntry = $this->ledgerEntryFromChange($change);
            $contractData = $ledgerEntry?->getData()->getContractData();
            if (!$contractData) {
                continue;
            }

            if ($this->scAddressToString($contractData->getContract()) !== config('yolixa.soroban.tip_router_contract_id')) {
                continue;
            }

            $key = $this->scValToNative($contractData->getKey());
            $value = $this->scValToMap($contractData->getBody()->getData()?->getVal());

            if (($key[0] ?? null) === 'Tip'
                && ($key[1] ?? null) === $intent->sender_wallet
                && (string) ($key[2] ?? '') === (string) $intent->contract_tip_id
            ) {
                $evidence['receipt'] = [
                    'contract_tip_id' => (string) ($value['tip_id'] ?? ''),
                    'sender' => (string) ($value['sender'] ?? ''),
                    'creator' => (string) ($value['creator'] ?? ''),
                    'token_contract_id' => (string) ($value['token'] ?? ''),
                    'gross_amount' => (string) ($value['gross_amount'] ?? ''),
                    'creator_amount' => (string) ($value['creator_amount'] ?? ''),
                    'platform_fee' => (string) ($value['platform_fee'] ?? ''),
                ];
            }

            if (($key[0] ?? null) === 'Creator' && ($key[1] ?? null) === $intent->receiver_wallet) {
                $evidence['creator_stats'] = [
                    'tip_count' => (string) ($value['tip_count'] ?? '0'),
                    'gross_received' => (string) ($value['gross_received'] ?? '0'),
                    'net_received' => (string) ($value['net_received'] ?? '0'),
                ];
            }
        }

        return $evidence;
    }

    private function ledgerEntryFromChange(XdrLedgerEntryChange $change)
    {
        return $change->getCreated() ?? $change->getUpdated() ?? $change->getState();
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

        if ($value->getU64() !== null || $value->getI64() !== null || $value->getI128() !== null) {
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
        if (!$value || $value->getMap() === null) {
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
            if ($parts->getHi() !== 0) {
                return '';
            }

            return (string) $parts->getLo();
        }

        return '';
    }
}
