<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('verification.notice'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
    }

    public function test_email_can_be_verified(): void
    {
        // Arrange
        Event::fake();
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('home') . '?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email@example.com')]
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertStatus(403);
    }

    public function test_verification_email_can_be_resent(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        // Act
        $response = $this->post(route('verification.store'));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_verified_users_are_redirected_from_verification_notice(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('verification.notice'));

        // Assert
        $response->assertRedirect(route('home'));
    }

    public function test_guests_cannot_access_email_verification_routes(): void
    {
        // Arrange
        $user = User::factory()->create(['email_verified_at' => null]);

        // Act & Assert
        $this->get(route('verification.notice'))
            ->assertRedirect(route('login'));

        $this->post(route('verification.store'))
            ->assertRedirect(route('login'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'));
    }

    public function test_verification_link_expires(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $expiredVerificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(60), // Expired
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Act
        $response = $this->get($expiredVerificationUrl);

        // Assert
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertStatus(403);
    }

    public function test_verification_throttling(): void
    {
        // Arrange
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        // Act - Make multiple requests quickly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('verification.store'));
        }

        // Assert - Should be throttled after too many attempts
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_user_cannot_verify_another_users_email(): void
    {
        // Arrange
        /** @var \App\Models\User $user1 */
        $user1 = User::factory()->create(['email_verified_at' => null]);
        /** @var \App\Models\User $user2 */
        $user2 = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user1);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user2->id, 'hash' => sha1($user2->email)]
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $this->assertFalse($user2->fresh()->hasVerifiedEmail());
        $response->assertStatus(403);
    }

    public function test_already_verified_email_cannot_be_verified_again(): void
    {
        // Arrange
        Event::fake();
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        Event::assertNotDispatched(Verified::class);
        $response->assertRedirect(route('home') . '?verified=1');
    }
}
