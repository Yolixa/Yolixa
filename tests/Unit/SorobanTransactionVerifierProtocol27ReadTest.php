<?php

namespace Tests\Unit;

use App\Models\TipIntent;
use App\Services\JsonSorobanRpcClient;
use App\Services\SorobanRpcException;
use App\Services\SorobanTransactionVerifier;
use App\Services\XlmAmount;
use Mockery;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Soroban\Address as SorobanAddress;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Tests\TestCase;

class SorobanTransactionVerifierProtocol27ReadTest extends TestCase
{
    private string $router;

    private string $token;

    private string $sender;

    private string $creator;

    private string $treasury;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = StrKey::encodeContractId(random_bytes(32));
        $this->token = StrKey::encodeContractId(random_bytes(32));
        $this->sender = KeyPair::random()->getAccountId();
        $this->creator = KeyPair::random()->getAccountId();
        $this->treasury = KeyPair::random()->getAccountId();

        config([
            'yolixa.soroban.tip_router_contract_id' => $this->router,
            'yolixa.soroban.xlm_token_contract_id' => $this->token,
            'yolixa.soroban.fee_bps' => 150,
            'yolixa.platform_public_key' => $this->treasury,
        ]);
    }

    public function test_successful_protocol_27_transaction_is_verified_with_contract_reads(): void
    {
        $result = $this->verifier()->verify($this->intent(), str_repeat('a', 64));

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertSame($this->router, $result['router_contract_id']);
        $this->assertSame($this->token, $result['token_contract_id']);
        $this->assertSame('42', $result['contract_tip_id']);
        $this->assertTrue($result['tip_exists']);
        $this->assertSame('9850000', $result['receipt']['creator_amount']);
    }

    public function test_failed_transaction_is_rejected_before_contract_reads(): void
    {
        $rpc = Mockery::mock(JsonSorobanRpcClient::class);
        $rpc->shouldReceive('getTransaction')->once()->andReturn(['status' => 'FAILED']);
        $rpc->shouldNotReceive('callContractRead');

        $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
            ->verify($this->intent(), str_repeat('b', 64));

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('retryable', $result);
    }

    public function test_pending_and_not_found_are_retryable(): void
    {
        foreach (['PENDING', 'NOT_FOUND'] as $status) {
            $rpc = Mockery::mock(JsonSorobanRpcClient::class);
            $rpc->shouldReceive('getTransaction')->once()->andReturn(['status' => $status]);
            $rpc->shouldNotReceive('callContractRead');

            $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
                ->verify($this->intent(), str_repeat('c', 64));

            $this->assertFalse($result['success']);
            $this->assertTrue($result['retryable']);
            $this->assertSame(strtolower($status), $result['status']);
        }
    }

    public function test_temporary_get_transaction_rpc_failure_is_retryable(): void
    {
        $rpc = Mockery::mock(JsonSorobanRpcClient::class);
        $rpc->shouldReceive('getTransaction')->once()->andThrow(SorobanRpcException::forHttpStatus(503));
        $rpc->shouldNotReceive('callContractRead');

        $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
            ->verify($this->intent(), str_repeat('c', 64));

        $this->assertFalse($result['success']);
        $this->assertTrue($result['retryable']);
    }

    public function test_temporary_router_read_rpc_failure_is_retryable(): void
    {
        $rpc = Mockery::mock(JsonSorobanRpcClient::class);
        $rpc->shouldReceive('getTransaction')->once()->andReturn([
            'status' => 'SUCCESS',
            'ledger' => 123456,
            'feeCharged' => '12345',
            'envelopeXdr' => $this->envelopeXdr(),
        ]);

        $rpc->shouldReceive('addressArgument')->andReturnUsing(
            fn (string $address) => SorobanAddress::fromAnyId($address)->toXdrSCVal()
        );
        $rpc->shouldReceive('u64Argument')->andReturnUsing(fn (string $value) => XdrSCVal::forU64((int) $value));
        $rpc->shouldReceive('callContractRead')->once()->andThrow(SorobanRpcException::forHttpStatus(429));

        $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
            ->verify($this->intent(), str_repeat('d', 64));

        $this->assertFalse($result['success']);
        $this->assertTrue($result['retryable']);
    }

    public function test_wrong_function_in_real_xdr_is_rejected(): void
    {
        $rpc = $this->rpcForSuccessfulReads($this->envelopeXdr('tip_split'));

        $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
            ->verify($this->intent(), str_repeat('d', 64));

        $this->assertFalse($result['success']);
    }

    public function test_mismatched_real_xdr_invocation_arguments_are_rejected(): void
    {
        foreach ([
            'sender' => ['sender' => KeyPair::random()->getAccountId()],
            'creator' => ['creator' => KeyPair::random()->getAccountId()],
            'token' => ['token' => StrKey::encodeContractId(random_bytes(32))],
            'amount' => ['amountAtomic' => '20000000'],
            'tip id' => ['tipId' => '43'],
        ] as $label => $override) {
            $rpc = $this->rpcForSuccessfulReads($this->envelopeXdr(overrides: $override));

            $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
                ->verify($this->intent(), str_repeat('f', 64));

            $this->assertFalse($result['success'], $label);
        }
    }

    public function test_mismatched_read_receipt_is_rejected(): void
    {
        $rpc = $this->rpcForSuccessfulReads($this->envelopeXdr(), [
            'get_tip' => [
                'tip_id' => '42',
                'sender' => $this->sender,
                'creator' => KeyPair::random()->getAccountId(),
                'token' => $this->token,
                'gross_amount' => '10000000',
                'creator_amount' => '9850000',
                'platform_fee' => '150000',
            ],
        ]);

        $result = (new SorobanTransactionVerifier($rpc, new XlmAmount))
            ->verify($this->intent(), str_repeat('e', 64));

        $this->assertFalse($result['success']);
    }

    private function verifier(): SorobanTransactionVerifier
    {
        return new SorobanTransactionVerifier($this->rpcForSuccessfulReads($this->envelopeXdr()), new XlmAmount);
    }

    private function rpcForSuccessfulReads(string $envelopeXdr, array $overrides = []): JsonSorobanRpcClient
    {
        $rpc = Mockery::mock(JsonSorobanRpcClient::class);
        $rpc->shouldReceive('getTransaction')->once()->andReturn([
            'status' => 'SUCCESS',
            'ledger' => 123456,
            'feeCharged' => '12345',
            'envelopeXdr' => $envelopeXdr,
        ]);

        $rpc->shouldReceive('addressArgument')->andReturnUsing(
            fn (string $address) => SorobanAddress::fromAnyId($address)->toXdrSCVal()
        );
        $rpc->shouldReceive('u64Argument')->andReturnUsing(fn (string $value) => XdrSCVal::forU64((int) $value));

        $reads = array_merge([
            'tip_exists' => true,
            'get_tip' => [
                'tip_id' => '42',
                'sender' => $this->sender,
                'creator' => $this->creator,
                'token' => $this->token,
                'gross_amount' => '10000000',
                'creator_amount' => '9850000',
                'platform_fee' => '150000',
            ],
            'get_creator_stats' => [
                'tip_count' => '1',
                'gross_received' => '10000000',
                'net_received' => '9850000',
            ],
            'get_fee_bps' => '150',
            'get_treasury' => $this->treasury,
            'is_paused' => false,
            'is_token_enabled' => true,
        ], $overrides);

        $rpc->shouldReceive('callContractRead')->andReturnUsing(
            fn (string $method) => $reads[$method] ?? null
        );

        return $rpc;
    }

    private function envelopeXdr(string $functionName = 'tip', array $overrides = []): string
    {
        $sender = $overrides['sender'] ?? $this->sender;
        $creator = $overrides['creator'] ?? $this->creator;
        $token = $overrides['token'] ?? $this->token;
        $amountAtomic = $overrides['amountAtomic'] ?? '10000000';
        $tipId = $overrides['tipId'] ?? '42';

        $hostFunction = new InvokeContractHostFunction($this->router, $functionName, [
            SorobanAddress::fromAccountId($sender)->toXdrSCVal(),
            SorobanAddress::fromAccountId($creator)->toXdrSCVal(),
            SorobanAddress::fromAnyId($token)->toXdrSCVal(),
            XdrSCVal::forI128Parts(0, (int) $amountAtomic),
            XdrSCVal::forU64((int) $tipId),
        ]);

        $operation = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
        $account = new Account($this->sender, new BigInteger('1000'));

        return (new TransactionBuilder($account))
            ->addOperation($operation)
            ->build()
            ->toEnvelopeXdrBase64();
    }

    private function intent(): TipIntent
    {
        return new TipIntent([
            'id' => 99,
            'sender_wallet' => $this->sender,
            'receiver_wallet' => $this->creator,
            'token_contract_id' => $this->token,
            'amount_atomic' => '10000000',
            'contract_tip_id' => 42,
        ]);
    }
}
