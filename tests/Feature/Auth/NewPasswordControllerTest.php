<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createPasswordResetToken(User $user): string
    {
        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);
        return $token;
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = Str::random(60);

        // Act
        $response = $this->get(route('password.reset', ['token' => $token]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
        $response->assertViewHas('request');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->createPasswordResetToken($user);
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $invalidToken = 'invalid-token';
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $invalidToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_email(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->createPasswordResetToken($user);
        $invalidEmail = 'invalid@example.com';
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $invalidEmail,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_password_reset_requires_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $response->assertSessionHasErrors(['token']);
    }

    public function test_password_reset_requires_email(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->createPasswordResetToken($user);
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_requires_password(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->createPasswordResetToken($user);

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_requires_password_confirmation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->createPasswordResetToken($user);
        $newPassword = 'new-password';

        // Act
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_updates_remember_token(): void
    {
        // Arrange
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $token = $this->createPasswordResetToken($user);
        $newPassword = 'new-password';
        $oldRememberToken = $user->remember_token;

        // Act
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Assert
        $user->refresh();
        $this->assertNotEquals($oldRememberToken, $user->remember_token);
        $this->assertNotNull($user->remember_token);
    }
}
