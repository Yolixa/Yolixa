<?php

namespace Tests\Feature;

use App\Models\Blockchain;
use App\Models\User;
use App\Models\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class CreatorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_registration_must_match_authenticated_wallet(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/creator/register', [
            'name' => 'Creator',
            'email' => 'creator@example.test',
            'blockchain_id' => $blockchain->id,
            'wallet_type' => $walletType->name,
            'public_key' => KeyPair::random()->getAccountId(),
        ]);

        $response->assertStatus(403);
        $this->assertSame('fan', $fan->fresh()->role);
    }

    public function test_creator_registration_upgrades_authenticated_wallet_only(): void
    {
        [$blockchain, $walletType] = $this->walletMetadata();
        $fan = $this->fan();

        $response = $this->actingAs($fan)->postJson('/creator/register', [
            'name' => 'Creator',
            'email' => 'creator@example.test',
            'blockchain_id' => $blockchain->id,
            'wallet_type' => $walletType->name,
            'public_key' => $fan->public_key,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        $fan->refresh();
        $this->assertSame('creator', $fan->role);
        $this->assertNotNull($fan->referral_key);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $fan->id,
            'public_key' => $fan->public_key,
            'wallet_type_id' => $walletType->id,
        ]);
    }

    public function test_creator_dashboard_cannot_be_opened_for_another_public_key(): void
    {
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.test',
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => 'creator',
            'status' => true,
        ]);

        $this->actingAs($creator)
            ->get('/dashboard/'.KeyPair::random()->getAccountId())
            ->assertStatus(404);
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

    private function walletMetadata(): array
    {
        $blockchain = Blockchain::create([
            'name' => 'Stellar',
            'symbol' => 'XLM',
            'active' => true,
        ]);

        $walletType = WalletType::create([
            'blockchain_id' => $blockchain->id,
            'name' => 'Freighter',
            'slug' => 'freighter',
        ]);

        return [$blockchain, $walletType];
    }
}
