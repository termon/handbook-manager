<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class PasswordResetLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        // Arrange & Act
        $response = $this->get(route('password.request'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_reset_link_can_be_requested(): void
    {
        // Arrange
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act
        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_requires_email(): void
    {
        // Arrange & Act
        $response = $this->post(route('password.email'), []);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_link_requires_valid_email_format(): void
    {
        // Arrange & Act
        $response = $this->post(route('password.email'), [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_link_fails_for_non_existent_user(): void
    {
        // Arrange
        Notification::fake();

        // Act
        $response = $this->post(route('password.email'), [
            'email' => null,
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        Notification::assertNothingSent();
    }

    public function test_authenticated_users_are_redirected_from_forgot_password(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('password.request'));

        // Assert
        $response->assertRedirect(route('home'));
    }

}
