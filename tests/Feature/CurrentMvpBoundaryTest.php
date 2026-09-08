<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class CurrentMvpBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_soroban_api_is_not_available_in_current_classic_mvp_mode(): void
    {
        config([
            'yolixa.tip_execution_mode' => 'classic',
            'yolixa.soroban.enabled' => false,
        ]);

        $fan = User::create([
            'name' => 'Fan',
            'email' => fake()->unique()->safeEmail(),
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => 'fan',
            'status' => true,
        ]);

        $this->getJson('/api/soroban/config')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->actingAs($fan)->postJson('/api/soroban/tip/intent', [])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
