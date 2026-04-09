<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_middleware_blocks_unauthenticated_users(): void
    {
        // Arrange & Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_auth_middleware_allows_authenticated_users(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
    }

    public function test_admin_role_functionality(): void
    {
        // Arrange
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $this->actingAs($admin);

        // Act & Assert
        $this->assertTrue(Auth::user()->role === Role::ADMIN);
        $this->assertEquals('admin', Auth::user()->role->value);
    }

    public function test_guest_role_functionality(): void
    {
        // Arrange
        /** @var \App\Models\User $guest */
        $guest = User::factory()->create(['role' => Role::GUEST]);
        $this->actingAs($guest);

        // Act & Assert
        $this->assertTrue(Auth::user()->role === Role::GUEST);
        $this->assertEquals('guest', Auth::user()->role->value);
    }

    public function test_role_enum_provides_options(): void
    {
        // Arrange, Act & Assert
        $options = Role::options();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('admin', $options);
        $this->assertArrayHasKey('user', $options);
        $this->assertArrayHasKey('guest', $options);
        $this->assertEquals('ADMIN', $options['admin']);
        $this->assertEquals('USER', $options['user']);
        $this->assertEquals('GUEST', $options['guest']);
    }

    public function test_role_enum_provides_values(): void
    {
        // Arrange, Act & Assert
        $values = Role::values();
        $this->assertIsArray($values);
        $this->assertContains('admin', $values);
        $this->assertContains('user', $values);
        $this->assertContains('guest', $values);
        $this->assertCount(3, $values);
    }

    public function test_user_model_has_correct_fillable_attributes(): void
    {
        // Arrange
        $user = new User();

        // Act & Assert
        $fillable = $user->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('role', $fillable);
    }

    public function test_user_model_has_correct_hidden_attributes(): void
    {
        // Arrange
        $user = new User();

        // Act & Assert
        $hidden = $user->getHidden();
        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_model_casts_role_to_enum(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => Role::ADMIN]);

        // Act & Assert
        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals(Role::ADMIN, $user->role);
    }

    public function test_user_model_hashes_password(): void
    {
        // Arrange & Act
        $user = User::factory()->create(['password' => 'plaintext-password']);

        // Assert
        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(password_verify('plaintext-password', $user->password));
    }

    public function test_user_factory_creates_valid_users(): void
    {
        // Arrange & Act
        $user = User::factory()->create();

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_remember_token_is_generated_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $user->setRememberToken('test-token');

        // Assert
        $this->assertEquals('test-token', $user->getRememberToken());
    }

    public function test_authentication_session_persistence(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // Act
        $this->actingAs($user);

        // Assert
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
        $this->assertEquals($user->email, Auth::user()->email);
    }

    public function test_logout_clears_authentication(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue(Auth::check());

        // Act
        Auth::logout();

        // Assert
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }

    public function test_route_protection_with_auth_middleware(): void
    {
        // Arrange
        $protectedRoutes = [
            'home',
            'about',
            'contact',
            'help',
            'settings.profile.edit',
            'settings.password.edit',
            'settings.appearance.edit',
        ];

        // Act & Assert
        foreach ($protectedRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertRedirect(route('login'));
        }
    }

    public function test_guest_route_protection(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $guestRoutes = [
            'login',
            'register',
            'password.request',
        ];

        // Act & Assert
        foreach ($guestRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertRedirect(route('home'));
        }
    }
}
