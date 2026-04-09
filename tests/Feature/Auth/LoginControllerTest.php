<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        // Arrange & Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_users_can_not_authenticate_with_invalid_email(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => 'invalid@example.com',
            'password' => 'password',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_login_requires_email(): void
    {
        // Arrange & Act
        $response = $this->post(route('login'), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_requires_password(): void
    {
        // Arrange & Act
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    public function test_login_requires_valid_email_format(): void
    {
        // Arrange & Act
        $response = $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_login(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_logout(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('logout'));

        // Assert
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_admin_user_can_login(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => Role::ADMIN,
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // Assert
        $this->assertAuthenticated();
        $this->assertEquals(Role::ADMIN, Auth::user()->role);
        $response->assertRedirect(route('home'));
    }

    public function test_guest_user_can_login(): void
    {
        // Arrange
        $guest = User::factory()->create([
            'email' => 'guest@example.com',
            'password' => Hash::make('password'),
            'role' => Role::GUEST,
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => 'guest@example.com',
            'password' => 'password',
        ]);

        // Assert
        $this->assertAuthenticated();
        $this->assertEquals(Role::GUEST, Auth::user()->role);
        $response->assertRedirect(route('home'));
    }

}
