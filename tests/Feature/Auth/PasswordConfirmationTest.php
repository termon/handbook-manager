<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('password.confirm'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.confirm-password');
    }

    public function test_password_can_be_confirmed(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->post(route('confirmation.store'), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_password_confirmation_fails_with_wrong_password(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->post(route('confirmation.store'), [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_confirmation_requires_password(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('confirmation.store'), []);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    public function test_guests_cannot_access_password_confirmation(): void
    {
        // Arrange & Act
        $response = $this->get(route('password.confirm'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_guests_cannot_confirm_password(): void
    {
        // Arrange & Act
        $response = $this->post(route('confirmation.store'), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_password_confirmation_with_intended_redirect(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Simulate accessing password.confirm with an intended URL
        session(['url.intended' => route('settings.profile.edit')]);

        // Act
        $response = $this->post(route('confirmation.store'), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect(route('settings.profile.edit'));
    }

    public function test_password_confirmation_throttling(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Act - Make multiple failed attempts
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('confirmation.store'), [
                'password' => 'wrong-password',
            ]);
        }

        // Final attempt should be throttled
        $response = $this->post(route('confirmation.store'), [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_successful_password_confirmation_sets_session(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Act
        $this->post(route('confirmation.store'), [
            'password' => 'password',
        ]);

        // Assert
        $this->assertTrue(session()->has('auth.password_confirmed_at'));
        $this->assertIsNumeric(session('auth.password_confirmed_at'));
    }

    public function test_password_confirmation_session_expires(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        // Set an expired password confirmation in session
        session(['auth.password_confirmed_at' => now()->subHours(4)->timestamp]);

        // Act - Access a route that requires recent password confirmation
        $response = $this->get(route('password.confirm'));

        // Assert - Should still render confirmation screen due to expired session
        $response->assertOk();
        $response->assertViewIs('auth.confirm-password');
    }
}
