<?php

namespace Tests\Unit;

use App\Models\TipIntent;
use App\Services\JsonSorobanRpcClient;
use App\Services\SorobanTransactionVerifier;
use App\Services\XlmAmount;
use Mockery;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Tests\TestCase;

class SorobanTransactionVerifierEvidenceTest extends TestCase
{
    private string $router;

    private string $token;

    private string $sender;

    private string $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->contractId();
        $this->token = $this->contractId();
        $this->sender = KeyPair::random()->getAccountId();
        $this->creator = KeyPair::random()->getAccountId();

        config([
            'yolixa.soroban.tip_router_contract_id' => $this->router,
            'yolixa.soroban.xlm_token_contract_id' => $this->token,
            'yolixa.soroban.fee_bps' => 150,
        ]);
    }

    public function test_evidence_accepts_matching_router_tip(): void
    {
        $result = $this->verifier()->verifyEvidence($this->intent(), $this->evidence());

        $this->assertTrue($result['success']);
        $this->assertSame('0.0150000', $result['platform_fee']);
        $this->assertSame('0.9850000', $result['creator_amount']);
    }

    public function test_evidence_rejects_mismatched_sender(): void
    {
        $evidence = $this->evidence();
        $evidence['sender'] = KeyPair::random()->getAccountId();

        $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success']);
    }

    public function test_evidence_rejects_wrong_router_function_token_amount_and_tip_id(): void
    {
        foreach ([
            ['router_contract_id', $this->contractId()],
            ['function', 'tip_split'],
            ['token_contract_id', $this->contractId()],
            ['amount_atomic', '9999999'],
            ['contract_tip_id', '43'],
        ] as [$key, $value]) {
            $evidence = $this->evidence();
            $evidence[$key] = $value;

            $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success'], $key);
        }
    }

    public function test_evidence_rejects_mismatched_creator_token_and_amount(): void
    {
        foreach ([
            ['creator', KeyPair::random()->getAccountId()],
            ['token_contract_id', $this->contractId()],
            ['amount_atomic', '20000000'],
        ] as [$key, $value]) {
            $evidence = $this->evidence();
            $evidence[$key] = $value;

            $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success']);
        }
    }

    public function test_evidence_rejects_bad_receipt(): void
    {
        $evidence = $this->evidence();
        $evidence['receipt']['platform_fee'] = '1';

        $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success']);
    }

    public function test_evidence_rejects_missing_receipt_and_tip_exists(): void
    {
        $missingReceipt = $this->evidence();
        unset($missingReceipt['receipt']);
        $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $missingReceipt)['success']);

        $missingExists = $this->evidence();
        $missingExists['tip_exists'] = false;
        $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $missingExists)['success']);
    }

    public function test_evidence_rejects_mismatched_creator_stats(): void
    {
        foreach ([
            ['tip_count', '0'],
            ['gross_received', '9999999'],
            ['net_received', '9849999'],
        ] as [$key, $value]) {
            $evidence = $this->evidence();
            $evidence['creator_stats'][$key] = $value;

            $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success'], $key);
        }
    }

    public function test_evidence_rejects_mismatched_router_config(): void
    {
        foreach ([
            ['fee_bps', '151'],
            ['xlm_enabled', false],
            ['paused', true],
        ] as [$key, $value]) {
            $evidence = $this->evidence();
            $evidence['router_config'][$key] = $value;

            $this->assertFalse($this->verifier()->verifyEvidence($this->intent(), $evidence)['success'], $key);
        }
    }

    private function verifier(): SorobanTransactionVerifier
    {
        return new SorobanTransactionVerifier(Mockery::mock(JsonSorobanRpcClient::class), new XlmAmount);
    }

    private function intent(): TipIntent
    {
        return new TipIntent([
            'sender_wallet' => $this->sender,
            'receiver_wallet' => $this->creator,
            'token_contract_id' => $this->token,
            'amount_atomic' => '10000000',
            'contract_tip_id' => 42,
        ]);
    }

    private function evidence(): array
    {
        $proof = [
            'contract_tip_id' => '42',
            'sender' => $this->sender,
            'creator' => $this->creator,
            'token_contract_id' => $this->token,
            'gross_amount' => '10000000',
            'creator_amount' => '9850000',
            'platform_fee' => '150000',
        ];

        return [
            'transaction_success' => true,
            'router_contract_id' => $this->router,
            'function' => 'tip',
            'sender' => $this->sender,
            'creator' => $this->creator,
            'token_contract_id' => $this->token,
            'amount_atomic' => '10000000',
            'contract_tip_id' => '42',
            'tip_exists' => true,
            'tip_event' => $proof,
            'receipt' => $proof,
            'creator_stats' => [
                'tip_count' => '1',
                'gross_received' => '10000000',
                'net_received' => '9850000',
            ],
            'router_config' => [
                'fee_bps' => '150',
                'treasury' => KeyPair::random()->getAccountId(),
                'paused' => false,
                'xlm_enabled' => true,
            ],
        ];
    }

    private function contractId(): string
    {
        return StrKey::encodeContractId(random_bytes(32));
    }
}
