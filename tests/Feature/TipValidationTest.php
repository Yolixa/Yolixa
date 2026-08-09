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

    public function test_sender_key_is_required_when_recording_tip(): void
    {
        $creator = $this->creator();

        $response = $this->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('a', 64),
            'amount' => 1,
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sender_key']);
    }

    public function test_self_tip_is_rejected_backend_side(): void
    {
        $creator = $this->creator();

        $response = $this->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('b', 64),
            'amount' => 1,
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

        $response = $this->postJson('/api/tip/record', [
            'tx_hash' => str_repeat('c', 64),
            'amount' => 1000.0000001,
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_duplicate_tx_hash_is_rejected(): void
    {
        $creator = $this->creator();
        $txHash = str_repeat('d', 64);

        Tip::create([
            'receiver_id' => $creator->id,
            'tx_hash' => $txHash,
            'amount' => 1,
            'asset' => 'XLM',
            'platform_fee' => 0.015,
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/tip/record', [
            'tx_hash' => $txHash,
            'amount' => 1,
            'asset' => 'XLM',
            'receiver_id' => $creator->id,
            'sender_key' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tx_hash']);
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
