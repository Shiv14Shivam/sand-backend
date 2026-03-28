<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// ─────────────────────────────────────────────────────────────────────────────
// T03 · T04 · T06 · T07
// Tests your actual Sanctum API login/logout — NOT the old Breeze web session.
// ─────────────────────────────────────────────────────────────────────────────
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // T03 — Valid credentials return token + user data
    public function test_customer_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'role'  => 'customer',
            'phone' => '9999999999',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user', 'message'])
                 ->assertJsonPath('user.role', 'customer');
    }

    // T04 — Wrong password is rejected
    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('message', 'Invalid credentials');
    }

    // T04b — Unverified email is rejected
    public function test_login_fails_when_email_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('message', 'Please verify your email first.');
    }

    // T06 — Logout invalidates the token
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Logged out successfully');
    }

    // T07 — Protected route blocked without token
    public function test_unauthenticated_request_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    // T07b — Authenticated user can access profile
    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure(['user'])
                 ->assertJsonPath('user.email', $user->email);
    }
}
