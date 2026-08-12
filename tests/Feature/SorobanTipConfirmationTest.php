<?php

namespace Tests\Feature;

use App\Models\Tip;
use App\Models\TipIntent;
use App\Models\User;
use App\Services\SorobanTransactionVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Tests\TestCase;

class SorobanTipConfirmationTest extends TestCase
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

    public function test_submission_persists_hash_without_creating_tip(): void
    {
        [$fan, , $intent] = $this->intent();
        $hash = str_repeat('a', 64);

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => $hash,
        ])->assertStatus(202)
            ->assertJsonPath('intent.status', 'submitted')
            ->assertJsonPath('intent.tx_hash', $hash);

        $this->assertCount(0, Tip::all());
        $this->assertSame('submitted', $intent->refresh()->status);
        $this->assertSame($hash, $intent->tx_hash);
    }

    public function test_submission_is_idempotent_for_same_hash_and_rejects_replacement(): void
    {
        [$fan, , $intent] = $this->intent();
        $hash = str_repeat('a', 64);

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => strtoupper($hash),
        ])->assertStatus(202);

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => $hash,
        ])->assertStatus(202);

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('b', 64),
        ])->assertStatus(422);

        $this->assertCount(0, Tip::all());
        $this->assertSame($hash, $intent->refresh()->tx_hash);
        $this->assertSame('submitted', $intent->status);
    }

    public function test_submission_rejects_wallet_mismatch(): void
    {
        [, , $intent] = $this->intent();
        $otherFan = $this->fan();

        $this->actingAs($otherFan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('c', 64),
        ])->assertStatus(422);

        $this->assertNull($intent->refresh()->tx_hash);
        $this->assertSame('pending', $intent->status);
    }

    public function test_submission_does_not_mutate_confirmed_intent_or_create_tip(): void
    {
        [$fan, , $intent] = $this->intent();
        $hash = str_repeat('d', 64);
        $intent->update([
            'status' => 'confirmed',
            'tx_hash' => $hash,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => $hash,
        ])->assertOk()
            ->assertJsonPath('already_confirmed', true)
            ->assertJsonPath('intent.status', 'confirmed');

        $this->actingAs($fan)->postJson('/api/soroban/tip/submitted', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('e', 64),
        ])->assertStatus(422);

        $this->assertCount(0, Tip::all());
        $this->assertSame($hash, $intent->refresh()->tx_hash);
        $this->assertSame('confirmed', $intent->status);
    }

    public function test_successful_confirmation_records_one_confirmed_tip(): void
    {
        [$fan, $creator, $intent] = $this->intent();
        $this->mockSuccessfulVerifier($intent);

        $response = $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('a', 64),
        ]);

        $response->assertOk()
            ->assertJsonPath('proof.verified_on_chain', true)
            ->assertJsonPath('proof.contract_tip_id', (string) $intent->contract_tip_id);

        $this->assertCount(1, Tip::all());
        $tip = Tip::first();
        $this->assertSame('confirmed', $tip->status);
        $this->assertSame('confirmed', $tip->soroban_status);
        $this->assertSame($this->router, $tip->router_contract_id);
        $this->assertSame($this->token, $tip->token_contract_id);
        $this->assertSame($creator->public_key, $tip->receiver_wallet);
    }

    public function test_duplicate_confirmation_returns_existing_tip_without_duplicate_record(): void
    {
        [$fan, , $intent] = $this->intent();
        $this->mockSuccessfulVerifier($intent);

        $payload = ['intent_id' => $intent->id, 'tx_hash' => str_repeat('b', 64)];

        $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', $payload)->assertOk();
        $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', $payload)->assertOk();

        $this->assertCount(1, Tip::all());
        $this->assertSame('confirmed', $intent->refresh()->status);
    }

    public function test_failed_verification_does_not_record_confirmed_tip(): void
    {
        [$fan, , $intent] = $this->intent();

        $this->mock(SorobanTransactionVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'success' => false,
                'message' => 'sender mismatch',
            ]);
        });

        $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('c', 64),
        ])->assertStatus(422);

        $this->assertCount(0, Tip::all());
        $this->assertSame('failed', $intent->refresh()->status);
    }

    public function test_retryable_verification_keeps_intent_submitted_without_tip(): void
    {
        [$fan, , $intent] = $this->intent();

        $this->mock(SorobanTransactionVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'success' => false,
                'retryable' => true,
                'message' => 'Soroban RPC is temporarily unavailable. Confirmation can be retried safely.',
            ]);
        });

        $response = $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('f', 64),
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('retryable', true);

        $this->assertCount(0, Tip::all());
        $this->assertSame('submitted', $intent->refresh()->status);
        $this->assertSame(str_repeat('f', 64), $intent->tx_hash);
    }

    public function test_confirmation_rejects_replacing_submitted_hash(): void
    {
        [$fan, , $intent] = $this->intent();
        $hash = str_repeat('1', 64);
        $intent->update([
            'status' => 'submitted',
            'tx_hash' => $hash,
        ]);

        $this->mock(SorobanTransactionVerifier::class, function ($mock) {
            $mock->shouldNotReceive('verify');
        });

        $this->actingAs($fan)->postJson('/api/soroban/tip/confirm', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('2', 64),
        ])->assertStatus(422);

        $this->assertCount(0, Tip::all());
        $this->assertSame($hash, $intent->refresh()->tx_hash);
        $this->assertSame('submitted', $intent->status);
    }

    public function test_confirmation_rejects_wallet_mismatch(): void
    {
        [, , $intent] = $this->intent();
        $otherFan = $this->fan();

        $this->actingAs($otherFan)->postJson('/api/soroban/tip/confirm', [
            'intent_id' => $intent->id,
            'tx_hash' => str_repeat('d', 64),
        ])->assertStatus(422);
    }

    private function mockSuccessfulVerifier(TipIntent $intent): void
    {
        $this->mock(SorobanTransactionVerifier::class, function ($mock) use ($intent) {
            $mock->shouldReceive('verify')->andReturn([
                'success' => true,
                'ledger' => 123,
                'network_fee_atomic' => '100',
                'creator_amount_atomic' => '9850000',
                'platform_fee_atomic' => '150000',
                'creator_amount' => '0.9850000',
                'platform_fee' => '0.0150000',
                'router_contract_id' => $this->router,
                'token_contract_id' => $this->token,
                'contract_tip_id' => (string) $intent->contract_tip_id,
                'receipt' => [
                    'contract_tip_id' => (string) $intent->contract_tip_id,
                    'sender' => $intent->sender_wallet,
                    'creator' => $intent->receiver_wallet,
                    'token_contract_id' => $intent->token_contract_id,
                    'gross_amount' => $intent->amount_atomic,
                    'creator_amount' => '9850000',
                    'platform_fee' => '150000',
                ],
                'creator_stats' => [
                    'tip_count' => '1',
                    'gross_received' => $intent->amount_atomic,
                    'net_received' => '9850000',
                ],
            ]);
        });
    }

    private function intent(): array
    {
        $fan = $this->fan();
        $creator = $this->creator();
        $intent = TipIntent::create([
            'sender_wallet' => $fan->public_key,
            'receiver_id' => $creator->id,
            'receiver_wallet' => $creator->public_key,
            'asset' => 'XLM',
            'token_contract_id' => $this->token,
            'amount' => '1.0000000',
            'amount_atomic' => '10000000',
            'contract_tip_id' => 42,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        return [$fan, $creator, $intent];
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
