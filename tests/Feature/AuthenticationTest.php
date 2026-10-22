<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_receive_a_sanctum_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@scooppos.test',
            'password' => Hash::make('correct-password'),
            'role' => UserRole::Owner,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', UserRole::Owner->value)
            ->assertJsonMissingPath('user.password');

        $this->assertIsString($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_an_invalid_password_without_creating_a_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The provided credentials are incorrect.')
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_validates_required_credentials(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_routes_reject_requests_without_a_bearer_token(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->postJson('/api/auth/logout')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_logout_revokes_only_the_token_used_for_the_request(): void
    {
        $user = User::factory()->create();
        $activeToken = $user->createToken('active-token')->plainTextToken;
        $otherToken = $user->createToken('other-token')->plainTextToken;

        $this->withToken($activeToken)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'active-token']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'other-token']);

        $this->app['auth']->forgetGuards();

        $this->withToken($activeToken)
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
