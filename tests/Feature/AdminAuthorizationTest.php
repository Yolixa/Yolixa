<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_access_admin_dashboard(): void
    {
        $fan = $this->user('fan');
        $creator = $this->user('creator');

        $this->actingAs($fan)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($creator)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_and_manage_creator_status(): void
    {
        $admin = $this->user('admin');
        $creator = $this->user('creator');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $this->actingAs($admin)->postJson("/admin/creators/{$creator->id}/manage", [
            'status' => false,
            'is_featured' => true,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $creator->refresh();
        $this->assertFalse((bool) $creator->status);
        $this->assertTrue((bool) $creator->is_featured);
    }

    public function test_admin_manage_creator_validates_fields_and_only_targets_creators(): void
    {
        $admin = $this->user('admin');
        $fan = $this->user('fan');

        $this->actingAs($admin)->postJson("/admin/creators/{$fan->id}/manage", [
            'status' => false,
        ])->assertStatus(404);

        $creator = $this->user('creator');
        $this->actingAs($admin)->postJson("/admin/creators/{$creator->id}/manage", [
            'status' => 'not-a-boolean',
        ])->assertStatus(422);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => fake()->unique()->safeEmail(),
            'public_key' => KeyPair::random()->getAccountId(),
            'role' => $role,
            'status' => true,
        ]);
    }
}
