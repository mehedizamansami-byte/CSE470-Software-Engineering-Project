<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 1: login with JWT through the API. */
class ApiJwtTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_returns_jwt_that_opens_me_endpoint(): void
    {
        User::factory()->create(['email' => 'api@example.com', 'password' => 'secret123', 'role' => 'designer']);

        $this->postJson('/api/login', ['email' => 'api@example.com', 'password' => 'bad'])->assertStatus(401);

        $token = $this->postJson('/api/login', ['email' => 'api@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->json('access_token');

        $this->assertCount(3, explode('.', $token));

        $this->getJson('/api/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('user.email', 'api@example.com')
            ->assertJsonPath('user.role', 'designer');

        $this->getJson('/api/me', ['Authorization' => 'Bearer '.$token.'x'])->assertStatus(401);
        $this->getJson('/api/me')->assertStatus(401);
    }
}
