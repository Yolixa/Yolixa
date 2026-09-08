<?php

namespace Tests\Unit;

use App\Services\StellarService;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Tests\TestCase;

class StellarSpendableBalanceTest extends TestCase
{
    public function test_spendable_preflight_preserves_minimum_balance_and_fee(): void
    {
        $account = $this->account(balance: '2.0000000', subentries: 0);

        $result = app(StellarService::class)->assessNativeXlmSpendability(
            $account,
            '15000000',
            1,
            5_000_000,
            100
        );

        $this->assertFalse($result['success']);
        $this->assertSame('1.0000000', $result['spendable_xlm']);
        $this->assertSame('1.5000100', $result['required_xlm']);
        $this->assertSame('1.0000000', $result['minimum_balance_xlm']);
    }

    public function test_spendable_preflight_accounts_for_subentries_sponsorship_and_liabilities(): void
    {
        $account = $this->account(
            balance: '4.0000000',
            subentries: 2,
            numSponsoring: 1,
            numSponsored: 1,
            sellingLiabilities: '0.5000000'
        );

        $result = app(StellarService::class)->assessNativeXlmSpendability(
            $account,
            '14999900',
            1,
            5_000_000,
            100
        );

        $this->assertTrue($result['success']);
        $this->assertSame('1.5000000', $result['spendable_xlm']);
        $this->assertSame('1.5000000', $result['required_xlm']);
        $this->assertSame('2.0000000', $result['minimum_balance_xlm']);
    }

    private function account(
        string $balance,
        int $subentries,
        int $numSponsoring = 0,
        int $numSponsored = 0,
        string $sellingLiabilities = '0.0000000'
    ): AccountResponse {
        $publicKey = KeyPair::random()->getAccountId();

        return AccountResponse::fromJson([
            'account_id' => $publicKey,
            'sequence' => '1',
            'subentry_count' => $subentries,
            'num_sponsoring' => $numSponsoring,
            'num_sponsored' => $numSponsored,
            'balances' => [[
                'balance' => $balance,
                'asset_type' => 'native',
                'selling_liabilities' => $sellingLiabilities,
            ]],
        ]);
    }
}
