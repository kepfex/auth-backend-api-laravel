<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthProfilesSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_not_available(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Otro',
            'email' => 'otro@example.com',
            'password' => 'Clave2026Segura',
        ])->assertNotFound();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'suspendido@example.com',
            'password' => 'Clave2026Segura',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'suspendido@example.com',
            'password' => 'Clave2026Segura',
        ])->assertUnauthorized();
    }

    public function test_active_user_without_person_has_empty_profiles(): void
    {
        $user = User::factory()->create(['is_active' => true, 'person_id' => null]);

        $this->actingAs($user, 'api')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('available_profiles', []);
    }

    public function test_inactive_user_with_existing_token_cannot_access_me(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = auth('api')->login($user);
        $user->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }
}
