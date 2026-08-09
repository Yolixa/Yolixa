<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ConversionService;
use App\Services\SorobanTipRegistryService;
use App\Services\StellarService;
use App\Services\TipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class TipServiceRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_reward_balance_increments_only_after_verified_tip(): void
    {
        config([
            'yolixa.ylx_price.XLM' => 0.1,
            'yolixa.ylx_reward_rate_percent' => 1,
        ]);

        $creator = $this->creator();
        $senderKey = KeyPair::random()->getAccountId();
        $stellar = Mockery::mock(StellarService::class);
        $stellar->shouldReceive('verifyTransaction')->once()->andReturn([
            'success' => true,
            'sender_wallet' => $senderKey,
            'receiver_wallet' => $creator->public_key,
            'platform_wallet' => KeyPair::random()->getAccountId(),
            'network_fee' => 0.00002,
            'platform_fee' => '0.1500000',
            'creator_payout_amount' => '9.8500000',
            'asset_issuer' => null,
        ]);

        $soroban = Mockery::mock(SorobanTipRegistryService::class);
        $soroban->shouldReceive('recordTip')->once()->andReturn(['success' => true, 'status' => 'disabled']);

        $service = new TipService($stellar, new ConversionService(), $soroban);

        $result = $service->recordTipSecurely([
            'tx_hash' => str_repeat('e', 64),
            'amount' => 10,
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $senderKey,
            'receiver_public_key' => $creator->public_key,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('9.85000000', (string) $result['tip']->creator_payout_amount);
        $this->assertSame('1.00000000', (string) $creator->fresh()->ylx_claimable_balance);
    }

    public function test_soroban_failure_does_not_fail_verified_tip(): void
    {
        $creator = $this->creator();
        $senderKey = KeyPair::random()->getAccountId();
        $stellar = Mockery::mock(StellarService::class);
        $stellar->shouldReceive('verifyTransaction')->once()->andReturn([
            'success' => true,
            'sender_wallet' => $senderKey,
            'receiver_wallet' => $creator->public_key,
            'platform_wallet' => KeyPair::random()->getAccountId(),
            'network_fee' => 0.00002,
            'platform_fee' => '0.0150000',
            'creator_payout_amount' => '0.9850000',
            'asset_issuer' => null,
        ]);

        $soroban = Mockery::mock(SorobanTipRegistryService::class);
        $soroban->shouldReceive('recordTip')->once()->andReturn([
            'success' => false,
            'status' => 'not_implemented',
            'message' => 'Scaffold only.',
        ]);

        $service = new TipService($stellar, new ConversionService(), $soroban);
        $result = $service->recordTipSecurely([
            'tx_hash' => str_repeat('f', 64),
            'amount' => 1,
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $senderKey,
            'receiver_public_key' => $creator->public_key,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('not_implemented', $result['tip']->fresh()->soroban_status);
    }

    private function creator(): User
    {
        return User::create([
            'name' => 'Creator',
            'email' => fake()->unique()->safeEmail(),
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => 'creator',
            'status' => true,
        ]);
    }
}
