<?php

namespace Tests\Unit;

use App\Services\StellarService;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Tests\TestCase;

class StellarTransactionVerificationTest extends TestCase
{
    public function test_matching_xlm_payment_operation_verifies(): void
    {
        $sender = KeyPair::random()->getAccountId();
        $receiver = KeyPair::random()->getAccountId();

        $result = app(StellarService::class)->verifyPaymentOperations([
            $this->payment($sender, $receiver, '2.5000000', 'native'),
        ], $receiver, '2.5000000', 'XLM', $sender);

        $this->assertTrue($result['success']);
        $this->assertSame($sender, $result['sender_wallet']);
    }

    public function test_wrong_receiver_wrong_amount_wrong_asset_and_wrong_sender_are_rejected(): void
    {
        $sender = KeyPair::random()->getAccountId();
        $receiver = KeyPair::random()->getAccountId();

        foreach ([
            [$this->payment($sender, KeyPair::random()->getAccountId(), '2.5000000', 'native'), $sender],
            [$this->payment($sender, $receiver, '2.4000000', 'native'), $sender],
            [$this->payment($sender, $receiver, '2.5000000', 'credit_alphanum4', 'USDC', KeyPair::random()->getAccountId()), $sender],
            [$this->payment(KeyPair::random()->getAccountId(), $receiver, '2.5000000', 'native'), $sender],
        ] as [$operation, $expectedSender]) {
            $result = app(StellarService::class)->verifyPaymentOperations([
                $operation,
            ], $receiver, '2.5000000', 'XLM', $expectedSender);

            $this->assertFalse($result['success']);
        }
    }

    private function payment(
        string $from,
        string $to,
        string $amount,
        string $assetType,
        ?string $assetCode = null,
        ?string $assetIssuer = null
    ): PaymentOperationResponse {
        return PaymentOperationResponse::fromJson(array_filter([
            'id' => '1',
            'paging_token' => '1',
            'source_account' => $from,
            'type' => 'payment',
            'type_i' => 1,
            'created_at' => now()->toIso8601String(),
            'transaction_hash' => str_repeat('a', 64),
            'transaction_successful' => true,
            'from' => $from,
            'to' => $to,
            'amount' => $amount,
            'asset_type' => $assetType,
            'asset_code' => $assetCode,
            'asset_issuer' => $assetIssuer,
        ], fn ($value) => $value !== null));
    }
}
