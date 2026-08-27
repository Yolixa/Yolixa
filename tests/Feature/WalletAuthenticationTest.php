<?php

namespace Tests\Feature;

use App\Models\Blockchain;
use App\Models\User;
use App\Models\WalletType;
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

    public function test_wallet_challenge_success_authenticates_wallet_user(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata();
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        $challengeResponse = $this->postJson('/auth/challenge', ['address' => $publicKey]);
        $challengeResponse->assertOk();

        $signature = base64_encode($keyPair->sign($challengeResponse->json('challenge')));

        $response = $this->postJson('/save-wallet', [
            'address' => $publicKey,
            'blockchainId' => $blockchain->id,
            'walletId' => $walletType->id,
            'status' => true,
            'signature' => $signature,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('public_key', $publicKey)
            ->assertJsonPath('role', 'fan');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'public_key' => $publicKey,
            'role' => 'fan',
            'status' => true,
        ]);
        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
            'blockchain_id' => $blockchain->id,
            'wallet_type_id' => $walletType->id,
        ]);
    }

    public function test_rabet_style_prefixed_hash_signature_authenticates_wallet_user(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata('Rabet', 'rabet');
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        $challengeResponse = $this->postJson('/auth/challenge', ['address' => $publicKey]);
        $challengeResponse->assertOk();

        $payload = "Stellar Signed Message:\n" . $challengeResponse->json('challenge');
        $signature = base64_encode($keyPair->sign(hash('sha256', $payload, true)));

        $response = $this->postJson('/save-wallet', [
            'address' => $publicKey,
            'blockchainId' => $blockchain->id,
            'walletId' => $walletType->id,
            'status' => true,
            'signature' => $signature,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('public_key', $publicKey)
            ->assertJsonPath('wallet_type_id', $walletType->id);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
            'wallet_type_id' => $walletType->id,
        ]);
    }

    public function test_enabled_wallet_options_include_freighter_and_rabet(): void
    {
        config(['yolixa.enabled_wallets' => ['freighter', 'rabet']]);

        $blockchain = Blockchain::create([
            'name' => 'Stellar',
            'symbol' => 'XLM',
            'active' => true,
        ]);

        foreach ([['Freighter', 'freighter'], ['Rabet', 'rabet'], ['WalletConnect', 'walletconnect']] as [$name, $slug]) {
            WalletType::create([
                'blockchain_id' => $blockchain->id,
                'name' => $name,
                'slug' => $slug,
            ]);
        }

        $response = $this->getJson("/get-wallets/{$blockchain->id}");

        $response->assertOk();

        $walletNames = collect($response->json('wallets'))->pluck('name')->sort()->values()->all();
        $this->assertSame(['Freighter', 'Rabet'], $walletNames);
    }

    public function test_wallet_challenge_rejects_invalid_signature(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata();
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        $challengeResponse = $this->postJson('/auth/challenge', ['address' => $publicKey]);
        $challengeResponse->assertOk();

        $wrongSignature = base64_encode(KeyPair::random()->sign($challengeResponse->json('challenge')));

        $this->postJson('/save-wallet', [
            'address' => $publicKey,
            'blockchainId' => $blockchain->id,
            'walletId' => $walletType->id,
            'status' => true,
            'signature' => $wrongSignature,
        ])->assertStatus(401);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['public_key' => $publicKey]);
    }

    public function test_wallet_challenge_rejects_expired_challenge(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata();
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        config(['yolixa.wallet_challenge_ttl_seconds' => 1]);

        $challengeResponse = $this->postJson('/auth/challenge', ['address' => $publicKey]);
        $challengeResponse->assertOk();

        $signature = base64_encode($keyPair->sign($challengeResponse->json('challenge')));
        $this->travel(2)->seconds();

        $this->postJson('/save-wallet', [
            'address' => $publicKey,
            'blockchainId' => $blockchain->id,
            'walletId' => $walletType->id,
            'status' => true,
            'signature' => $signature,
        ])->assertStatus(401);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['public_key' => $publicKey]);
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

    private function walletMetadata(string $walletName = 'Freighter', string $walletSlug = 'freighter'): array
    {
        $blockchain = Blockchain::create([
            'name' => 'Stellar',
            'symbol' => 'XLM',
            'active' => true,
        ]);

        $walletType = WalletType::create([
            'blockchain_id' => $blockchain->id,
            'name' => $walletName,
            'slug' => $walletSlug,
        ]);

        return [$blockchain, $walletType];
    }
}
