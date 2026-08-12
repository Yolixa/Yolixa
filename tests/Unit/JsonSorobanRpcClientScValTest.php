<?php

namespace Tests\Unit;

use App\Services\JsonSorobanRpcClient;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Soroban\Address as SorobanAddress;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Tests\TestCase;

class JsonSorobanRpcClientScValTest extends TestCase
{
    public function test_result_ok_values_are_unwrapped_from_router_reads(): void
    {
        $this->assertSame(150, $this->decode(XdrSCVal::forVec([
            XdrSCVal::forSymbol('Ok'),
            XdrSCVal::forU32(150),
        ])));
    }

    public function test_result_err_values_are_rejected_from_router_reads(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Router read returned a contract error.');

        $this->decode(XdrSCVal::forVec([
            XdrSCVal::forSymbol('Err'),
            XdrSCVal::forU32(2),
        ]));
    }

    public function test_option_like_single_vectors_and_struct_maps_decode_to_native_values(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $receipt = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('sender'), SorobanAddress::fromAccountId($accountId)->toXdrSCVal()),
            new XdrSCMapEntry(XdrSCVal::forSymbol('gross_amount'), XdrSCVal::forI128Parts(0, 10000000)),
        ]);

        $decoded = $this->decode(XdrSCVal::forVec([$receipt]));

        $this->assertSame($accountId, $decoded['sender']);
        $this->assertSame('10000000', $decoded['gross_amount']);
    }

    private function decode(XdrSCVal $value): mixed
    {
        $method = new \ReflectionMethod(JsonSorobanRpcClient::class, 'scValToNative');
        $method->setAccessible(true);

        return $method->invoke(new JsonSorobanRpcClient, $value);
    }
}
