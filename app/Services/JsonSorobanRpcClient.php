<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address as SorobanAddress;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class JsonSorobanRpcClient
{
    private array $readClients = [];

    public function getTransaction(string $txHash): array
    {
        return $this->request('getTransaction', ['hash' => $txHash]);
    }

    public function callContractRead(string $method, array $arguments = [], ?string $sourceAccount = null): mixed
    {
        $result = $this->readClient($sourceAccount)->invokeMethod(
            $method,
            $arguments,
            false,
            new MethodOptions(simulate: true, restore: false)
        );

        return $this->scValToNative($result);
    }

    private function readClient(?string $sourceAccount): SorobanClient
    {
        $sourceAccount = $sourceAccount ?: $this->defaultReadSourceAccount();
        $key = implode('|', [
            $sourceAccount,
            (string) config('yolixa.soroban.tip_router_contract_id'),
            (string) config('yolixa.soroban.rpc_url'),
        ]);

        if (! isset($this->readClients[$key])) {
            $this->readClients[$key] = SorobanClient::forClientOptions(new ClientOptions(
                sourceAccountKeyPair: KeyPair::fromAccountId($sourceAccount),
                contractId: (string) config('yolixa.soroban.tip_router_contract_id'),
                network: new Network((string) config('yolixa.stellar_passphrase', 'Test SDF Network ; September 2015')),
                rpcUrl: (string) config('yolixa.soroban.rpc_url')
            ));
        }

        return $this->readClients[$key];
    }

    private function request(string $method, array $params): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->post((string) config('yolixa.soroban.rpc_url'), [
                'jsonrpc' => '2.0',
                'id' => (string) Str::uuid(),
                'method' => $method,
                'params' => $params,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Soroban RPC is unavailable.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Soroban RPC returned an invalid response.');
        }

        if (isset($payload['error'])) {
            $message = $payload['error']['message'] ?? 'Soroban RPC rejected the request.';
            throw new RuntimeException($message);
        }

        return $payload['result'] ?? [];
    }

    private function defaultReadSourceAccount(): string
    {
        $source = (string) config('yolixa.soroban.verifier_source_account', '');

        if ($source !== '') {
            return $source;
        }

        $platform = (string) config('yolixa.platform_public_key', '');
        if ($platform !== '') {
            return $platform;
        }

        throw new RuntimeException('A public Soroban verifier source account is required for read-only contract simulation.');
    }

    public function addressArgument(string $address): XdrSCVal
    {
        $parsed = SorobanAddress::fromAnyId($address);

        if (! $parsed) {
            throw new RuntimeException('Invalid Soroban address argument.');
        }

        return $parsed->toXdrSCVal();
    }

    public function i128Argument(string $amount): XdrSCVal
    {
        return XdrSCVal::forI128BigInt($amount);
    }

    public function u64Argument(string $value): XdrSCVal
    {
        if (! preg_match('/^\d+$/', $value)) {
            throw new RuntimeException('Invalid unsigned integer argument.');
        }

        if (strlen($value) > 18 || (strlen($value) === 18 && strcmp($value, (string) PHP_INT_MAX) > 0)) {
            return XdrSCVal::fromJsonValue(['u64' => $value]);
        }

        return XdrSCVal::forU64((int) $value);
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

        if ($value->getI32() !== null) {
            return $value->getI32();
        }

        if ($value->getU32() !== null) {
            return $value->getU32();
        }

        if ($value->getU64() !== null || $value->getI64() !== null || $value->getU128() !== null || $value->getI128() !== null) {
            return $this->scValToIntegerString($value);
        }

        if ($value->getAddress() !== null) {
            return SorobanAddress::fromXdr($value->getAddress())->toStrKey();
        }

        if ($value->getVec() !== null) {
            $vec = array_map(fn (XdrSCVal $item) => $this->scValToNative($item), $value->getVec());
            if (($vec[0] ?? null) === 'Ok') {
                return $vec[1] ?? null;
            }

            if (($vec[0] ?? null) === 'Err') {
                throw new RuntimeException('Router read returned a contract error.');
            }

            return count($vec) === 1 ? $vec[0] : $vec;
        }

        if ($value->getMap() !== null) {
            $map = [];
            foreach ($value->getMap() as $entry) {
                $key = $this->scValToNative($entry->getKey());
                if (is_string($key)) {
                    $map[$key] = $this->scValToNative($entry->getVal());
                }
            }

            return $map;
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
