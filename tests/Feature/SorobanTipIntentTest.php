<?php

namespace Tests\Feature;

use App\Models\TipIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Tests\TestCase;

class SorobanTipIntentTest extends TestCase
{
    use RefreshDatabase;

    private string $router;
    private string $token;
    private string $treasury;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = StrKey::encodeContractId(random_bytes(32));
        $this->token = StrKey::encodeContractId(random_bytes(32));
        $this->treasury = KeyPair::random()->getAccountId();

        config([
            'yolixa.tip_execution_mode' => 'soroban',
            'yolixa.soroban.enabled' => true,
            'yolixa.soroban.tip_router_contract_id' => $this->router,
            'yolixa.soroban.xlm_token_contract_id' => $this->token,
            'yolixa.soroban.rpc_url' => 'https://soroban-testnet.stellar.org',
            'yolixa.platform_public_key' => $this->treasury,
            'yolixa.network' => 'testnet',
            'yolixa.stellar_passphrases.testnet' => 'Test SDF Network ; September 2015',
        ]);
    }

    public function test_soroban_config_exposes_only_public_values(): void
    {
        $response = $this->getJson('/api/soroban/config');

        $response->assertOk()
            ->assertJsonPath('config.routerContractId', $this->router)
            ->assertJsonPath('config.xlmTokenContractId', $this->token)
            ->assertJsonMissing(['SOROBAN_PLATFORM_SIGNER_SECRET']);
    }

    public function test_unauthenticated_wallet_is_blocked(): void
    {
        $creator = $this->creator();

        $this->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creator->id,
            'amount' => '1',
            'asset' => 'XLM',
        ])->assertStatus(401);
    }

    public function test_intent_creation_uses_session_wallet_and_is_idempotent(): void
    {
        $fan = $this->fan();
        $creator = $this->creator();

        $payload = [
            'receiver_id' => $creator->id,
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'sender' => $fan->public_key,
        ];

        $first = $this->actingAs($fan)->postJson('/api/soroban/tip/intent', $payload);
        $second = $this->actingAs($fan)->postJson('/api/soroban/tip/intent', $payload);

        $first->assertOk()
            ->assertJsonPath('sender', $fan->public_key)
            ->assertJsonPath('creator', $creator->public_key)
            ->assertJsonPath('amount_atomic', '10000000')
            ->assertJsonPath('token_contract_id', $this->token)
            ->assertJsonPath('router_contract_id', $this->router);

        $this->assertSame($first->json('intent_id'), $second->json('intent_id'));
        $this->assertSame($first->json('contract_tip_id'), $second->json('contract_tip_id'));
        $this->assertCount(1, TipIntent::all());
    }

    public function test_missing_treasury_public_key_rejects_soroban_intent(): void
    {
        config(['yolixa.platform_public_key' => null]);

        $fan = $this->fan();
        $creator = $this->creator();

        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creator->id,
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'sender' => $fan->public_key,
        ])->assertStatus(422);
    }

    public function test_wallet_session_mismatch_is_blocked(): void
    {
        $fan = $this->fan();
        $creator = $this->creator();

        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creator->id,
            'amount' => '1',
            'asset' => 'XLM',
            'sender' => KeyPair::random()->getAccountId(),
        ])->assertStatus(422);
    }

    public function test_invalid_creator_self_tip_invalid_amount_and_unsupported_asset_are_blocked(): void
    {
        $fan = $this->fan();
        $otherFan = $this->fan();

        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $otherFan->id,
            'amount' => '1',
            'asset' => 'XLM',
            'sender' => $fan->public_key,
        ])->assertStatus(422);

        $creatorWithSameWallet = $this->creator();

        $this->actingAs($creatorWithSameWallet)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creatorWithSameWallet->id,
            'amount' => '1',
            'asset' => 'XLM',
            'sender' => $creatorWithSameWallet->public_key,
        ])->assertStatus(422);

        $creator = $this->creator();
        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creator->id,
            'amount' => '1.00000001',
            'asset' => 'XLM',
            'sender' => $fan->public_key,
        ])->assertStatus(422);

        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
            'receiver_id' => $creator->id,
            'amount' => '1',
            'asset' => 'USDC',
            'sender' => $fan->public_key,
        ])->assertStatus(422);
    }

    public function test_soroban_intent_rejects_malformed_amounts_and_configured_limits(): void
    {
        config([
            'yolixa.min_payment_amount' => '1',
            'yolixa.max_payment_amount' => '2',
        ]);

        $fan = $this->fan();
        $creator = $this->creator();

        foreach (['abc', '-1', '0.9999999', '2.0000001'] as $amount) {
            $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [
                'receiver_id' => $creator->id,
                'amount' => $amount,
                'asset' => 'XLM',
                'sender' => $fan->public_key,
            ])->assertStatus(422);
        }

        $this->assertCount(0, TipIntent::all());
    }

    public function test_classic_endpoint_is_only_available_in_classic_mode(): void
    {
        $this->postJson('/api/tip/build-xdr', [])->assertStatus(409);

        config(['yolixa.tip_execution_mode' => 'classic']);

        $this->postJson('/api/tip/build-xdr', [])->assertStatus(422);
    }

    private function fan(): User
    {
        return User::create([
            'name' => 'Fan',
            'email' => fake()->unique()->safeEmail(),
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => 'fan',
            'status' => true,
        ]);
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
