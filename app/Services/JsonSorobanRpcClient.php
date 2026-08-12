<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class JsonSorobanRpcClient
{
    public function getTransaction(string $txHash): array
    {
        return $this->request('getTransaction', ['hash' => $txHash]);
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

        if (!$response->successful()) {
            throw new RuntimeException('Soroban RPC is unavailable.');
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('Soroban RPC returned an invalid response.');
        }

        if (isset($payload['error'])) {
            $message = $payload['error']['message'] ?? 'Soroban RPC rejected the request.';
            throw new RuntimeException($message);
        }

        return $payload['result'] ?? [];
    }
}
