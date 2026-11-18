<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 1: authentication and account management. */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_requires_otp_then_unlocks_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.com',
            'phone' => '01712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'seller',
        ]);

        $response->assertRedirect(route('otp.show'));
        $code = session('demo_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // Not verified yet: the dashboard sends the user back to the OTP page.
        $this->get('/dashboard')->assertRedirect(route('otp.show'));

        $this->post('/verify', ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/verify', ['code' => $code])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'nusrat@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(20, $user->ai_credits); // Free plan welcome credits
        $this->get('/dashboard')->assertOk()->assertSee('Nusrat Jahan');
    }

    public function test_admin_role_cannot_self_register(): void
    {
        $this->post('/register', [
            'name' => 'Hacker', 'email' => 'h@example.com', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'role' => 'admin',
        ])->assertSessionHasErrors('role');
    }

    public function test_login_logout_and_suspended_account(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'secret123']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong-pass'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('login'));

        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_forgot_and_reset_password_with_code(): void
    {
        User::factory()->create(['email' => 'b@example.com', 'password' => 'oldpass123']);

        $this->post('/forgot-password', ['email' => 'b@example.com'])->assertRedirect();
        $code = session('demo_code');
        $this->assertNotNull($code);

        $this->post('/reset-password', ['email' => 'b@example.com', 'code' => '999999' === $code ? '888888' : '999999', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertSessionHasErrors('code');

        $this->post('/reset-password', ['email' => 'b@example.com', 'code' => $code, 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'b@example.com', 'password' => 'newpass123'])->assertRedirect(route('dashboard'));
    }

    public function test_deactivate_then_login_reactivates(): void
    {
        $user = User::factory()->create(['email' => 'c@example.com', 'password' => 'secret123']);

        $this->actingAs($user)->post('/account/deactivate', ['deactivate_password' => 'secret123'])->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->deactivated_at);

        $this->post('/login', ['email' => 'c@example.com', 'password' => 'secret123'])->assertSessionHas('success');
        $this->assertNull($user->fresh()->deactivated_at);
    }

    public function test_delete_account_needs_password_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->actingAs($user)->delete('/account', ['delete_password' => 'secret123', 'confirm_text' => 'nope'])->assertSessionHasErrors('confirm_text');
        $this->actingAs($user)->delete('/account', ['delete_password' => 'secret123', 'confirm_text' => 'DELETE'])->assertRedirect(route('login'));
        $this->assertModelMissing($user);
    }
}
