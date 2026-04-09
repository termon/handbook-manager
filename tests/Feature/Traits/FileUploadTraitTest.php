<?php

namespace Tests\Feature\Traits;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_uploaded_avatar_file(): void
    {
        Storage::fake('public');

        $user = $this->uploadedAvatarUser();
        $user->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]));
        $user->save();

        $this->assertIsString($user->avatar);
        $this->assertFalse(str_starts_with($user->avatar, 'data:image/'));
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertSame(basename($user->avatar), $user->fileName('avatar'));
        $this->assertTrue($user->hasFile('avatar'));
        $this->assertTrue($user->avatar_is_image);
    }

    public function test_user_replaces_old_avatar_on_update(): void
    {
        Storage::fake('public');

        $user = $this->uploadedAvatarUser();
        $user->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ]));
        $user->save();

        $oldPath = $user->avatar;

        $user->update([
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_user_can_store_avatar_as_base64_image(): void
    {
        $user = $this->base64AvatarUser();
        $user->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]));
        $user->save();

        $this->assertIsString($user->avatar);
        $this->assertStringStartsWith('data:image/', $user->avatar);
        $this->assertSame('file.jpeg', $user->fileName('avatar'));
        $this->assertTrue($user->hasFile('avatar'));
        $this->assertTrue($user->avatar_is_image);
    }

    public function test_user_avatar_accessors_identify_remote_non_image_url(): void
    {
        $url = 'https://example.com/files/manual.pdf?download=1';

        $user = $this->uploadedAvatarUser();
        $user->fill($this->userAttributes([
            'avatar' => $url,
        ]));
        $user->save();

        $this->assertFalse($user->avatar_is_image);
        $this->assertSame('manual.pdf', $user->avatar_name);
        $this->assertSame($url, $user->avatar_url);
        $this->assertTrue($user->avatar_exists);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function userAttributes(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Joe Bloggs',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => Role::USER,
        ], $overrides);
    }

    private function uploadedAvatarUser(): User
    {
        return new class extends User {
            protected $table = 'users';

            protected function fileUploads(): array
            {
                return [
                    'avatar' => [
                        'folder' => 'users/avatars',
                        'as_base64' => false,
                    ],
                ];
            }
        };
    }

    private function base64AvatarUser(): User
    {
        return new class extends User {
            protected $table = 'users';

            protected function fileUploads(): array
            {
                return [
                    'avatar' => [
                        'as_base64' => true,
                    ],
                ];
            }
        };
    }
}
