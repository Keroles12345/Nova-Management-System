<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_a_jwt_and_never_accepts_a_client_selected_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Member',
            'email' => 'member@example.test',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'user');

        $token = $response->json('access_token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'member@example.test');
    }

    public function test_logout_invalidates_previously_issued_tokens(): void
    {
        $user = User::factory()->create();
        $token = app(\App\Services\JwtTokenService::class)->issue($user)['token'];

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_invalid_or_tampered_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = app(\App\Services\JwtTokenService::class)->issue($user)['token'];
        $tampered = substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a');

        $this->withToken($tampered)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_user_management_api_requires_an_admin_role(): void
    {
        $user = User::factory()->create();
        $token = app(\App\Services\JwtTokenService::class)->issue($user)['token'];

        $this->withToken($token)->getJson('/api/users')->assertForbidden();

        $user->update(['role' => 'admin']);
        $adminToken = app(\App\Services\JwtTokenService::class)->issue($user)['token'];

        $this->withToken($adminToken)->getJson('/api/users')->assertOk();
    }
}
