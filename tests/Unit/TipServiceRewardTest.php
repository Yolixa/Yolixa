<?php

namespace Tests\Unit;

use App\Models\User;
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

    public function test_verified_current_mvp_tip_records_direct_xlm_without_rewards_or_platform_fee(): void
    {
        config(['yolixa.soroban.enabled' => false]);

        $creator = $this->creator();
        $senderKey = KeyPair::random()->getAccountId();
        $stellar = Mockery::mock(StellarService::class);
        $stellar->shouldReceive('verifyTransaction')->once()->andReturn([
            'success' => true,
            'sender_wallet' => $senderKey,
            'receiver_wallet' => $creator->public_key,
            'network_fee' => '0.0000100',
            'platform_fee' => '0.0000000',
            'creator_payout_amount' => '10.0000000',
            'asset_issuer' => null,
        ]);

        $soroban = Mockery::mock(SorobanTipRegistryService::class);
        $soroban->shouldNotReceive('recordTip');

        $service = new TipService($stellar, $soroban);

        $result = $service->recordTipSecurely([
            'tx_hash' => str_repeat('e', 64),
            'amount' => '10.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $senderKey,
            'receiver_public_key' => $creator->public_key,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('10.00000000', (string) $result['tip']->creator_payout_amount);
        $this->assertSame('0.00000000', (string) $result['tip']->platform_fee);
        $this->assertSame('not_available_current_mvp', $result['tip']->ylx_reward_status);
        $this->assertSame('0.00000000', (string) $creator->fresh()->ylx_claimable_balance);
    }

    public function test_duplicate_transaction_hash_is_rejected_by_tip_service(): void
    {
        config(['yolixa.soroban.enabled' => false]);

        $creator = $this->creator();
        $senderKey = KeyPair::random()->getAccountId();
        $stellar = Mockery::mock(StellarService::class);
        $stellar->shouldReceive('verifyTransaction')->once()->andReturn([
            'success' => true,
            'sender_wallet' => $senderKey,
            'receiver_wallet' => $creator->public_key,
            'network_fee' => '0.0000100',
            'platform_fee' => '0.0000000',
            'creator_payout_amount' => '1.0000000',
            'asset_issuer' => null,
        ]);

        $soroban = Mockery::mock(SorobanTipRegistryService::class);
        $soroban->shouldNotReceive('recordTip');

        $service = new TipService($stellar, $soroban);
        $payload = [
            'tx_hash' => str_repeat('f', 64),
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $senderKey,
            'receiver_public_key' => $creator->public_key,
        ];

        $this->assertTrue($service->recordTipSecurely($payload)['success']);
        $result = $service->recordTipSecurely([
            'tx_hash' => str_repeat('f', 64),
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $senderKey,
            'receiver_public_key' => $creator->public_key,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('This transaction hash has already been recorded.', $result['message']);
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
