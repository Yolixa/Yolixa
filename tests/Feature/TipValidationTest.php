<?php

namespace Tests\Feature;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class TipValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['yolixa.tip_execution_mode' => 'classic']);
    }

    public function test_sender_key_is_required_when_recording_tip(): void
    {
        $creator = $this->creator();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('a', 64),
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sender_key']);
    }

    public function test_self_tip_is_rejected_backend_side(): void
    {
        $creator = $this->creator();

        $response = $this->actingAs($creator)->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('b', 64),
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $creator->public_key,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Self tip blocked.');
    }

    public function test_amount_above_max_is_rejected(): void
    {
        config(['yolixa.max_payment_amount' => 1000]);
        $creator = $this->creator();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('c', 64),
            'amount' => '1000.0000001',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $fan->public_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_duplicate_tx_hash_is_rejected(): void
    {
        $creator = $this->creator();
        $txHash = str_repeat('d', 64);

        Tip::create([
            'receiver_id' => $creator->id,
            'tx_hash' => $txHash,
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'platform_fee' => 0,
            'status' => 'confirmed',
        ]);

        $fan = $this->fan();
        $response = $this->actingAs($fan)->postJson('/api/tip/record', [
            'tx_hash' => $txHash,
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $fan->public_key,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tx_hash']);
    }

    public function test_malformed_tx_hash_is_rejected_before_horizon_verification(): void
    {
        $creator = $this->creator();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/api/tip/record', [
            'tx_hash' => 'not-a-stellar-hash',
            'amount' => '1.0000000',
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => $fan->public_key,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tx_hash']);
    }

    public function test_build_xdr_requires_authenticated_wallet_session(): void
    {
        $creator = $this->creator();

        $response = $this->postJson('/api/tip/build-xdr', [
            'amount' => '1.0000000',
            'destination' => $creator->public_key,
            'asset' => 'XLM',
            'sender' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(401);
    }

    public function test_build_xdr_rejects_sender_that_does_not_match_session(): void
    {
        $creator = $this->creator();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/api/tip/build-xdr', [
            'amount' => '1.0000000',
            'destination' => $creator->public_key,
            'asset' => 'XLM',
            'sender' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(403);
    }

    public function test_current_mvp_rejects_unsupported_tip_asset(): void
    {
        $creator = $this->creator();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/api/tip/build-xdr', [
            'amount' => '1.0000000',
            'destination' => $creator->public_key,
            'asset' => 'USDC',
            'sender' => $fan->public_key,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['asset']);
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
}
