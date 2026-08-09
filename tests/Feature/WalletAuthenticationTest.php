<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class WalletAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_challenge_rejects_invalid_public_key(): void
    {
        $response = $this->postJson('/auth/challenge', ['address' => 'bad-key']);

        $response->assertStatus(422);
    }

    public function test_wallet_challenge_can_be_used_once(): void
    {
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        $challengeResponse = $this->postJson('/auth/challenge', ['address' => $publicKey]);
        $challengeResponse->assertOk();

        $challenge = $challengeResponse->json('challenge');
        $signature = base64_encode($keyPair->sign($challenge));

        $payload = [
            'address' => $publicKey,
            'blockchainId' => null,
            'walletId' => null,
            'status' => true,
            'signature' => $signature,
        ];

        $first = $this->postJson('/save-wallet', $payload);
        $first->assertStatus(422);

        $second = $this->postJson('/save-wallet', $payload);
        $second->assertStatus(401);
    }

    public function test_disconnect_rejects_a_different_wallet(): void
    {
        $user = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.test',
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => 'creator',
            'status' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/disconnect-wallet', [
            'address' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(403);
    }
}
