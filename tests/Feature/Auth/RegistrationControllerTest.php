<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        // Arrange & Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_new_users_can_register(): void
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->post(route('register'), $userData);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(route('home'));
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertEquals(Role::GUEST, $user->role); // Default role should be guest
    }

    public function test_new_users_can_register_with_an_avatar(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Avatar User',
            'email' => 'avatar@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'avatar@example.com')->first();

        $this->assertNotNull($user);
        $this->assertStringStartsWith('data:image/', (string) $user->avatar);
        $this->assertTrue($user->avatar_exists);
        $this->assertTrue($user->avatar_is_image);
    }

    public function test_registration_requires_name(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['name']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_email(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['name' => 'Test User']);
    }

    public function test_registration_requires_valid_email(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['name' => 'Test User']);
    }

    public function test_registration_requires_unique_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'test@example.com']);

        // Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }

    public function test_registration_requires_password(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => null,
            'password_confirmation' => '123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_authenticated_users_are_redirected_from_registration(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertRedirect(route('home'));
    }

    public function test_registration_sets_default_guest_role(): void
    {
        // Arrange & Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $this->assertAuthenticated();
        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals(Role::GUEST, $user->role);
    }

    public function test_name_cannot_be_longer_than_255_characters(): void
    {
        // Arrange
        $longName = str_repeat('a', 256);

        // Act
        $response = $this->post(route('register'), [
            'name' => $longName,
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['name']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_email_cannot_be_longer_than_255_characters(): void
    {
        // Arrange
        $longEmail = str_repeat('a', 250) . '@example.com'; // Over 255 chars

        // Act
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['name' => 'Test User']);
    }
}
