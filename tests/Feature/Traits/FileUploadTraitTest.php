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
        $this->assertSame('users/avatar.jpg', $user->avatar);
        $this->assertFalse(str_starts_with($user->avatar, 'data:image/'));
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertSame(basename($user->avatar), $user->fileName('avatar'));
        $this->assertTrue($user->hasFile('avatar'));
        $this->assertTrue($user->avatar_is_image);
    }

    public function test_user_overwrites_existing_file_with_same_original_name_by_default(): void
    {
        Storage::fake('public');

        $firstUser = $this->uploadedAvatarUser();
        $firstUser->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ]));
        $firstUser->save();

        $secondUser = $this->uploadedAvatarUser();
        $secondUser->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ]));
        $secondUser->save();

        $this->assertSame('users/first.jpg', $firstUser->avatar);
        $this->assertSame($firstUser->avatar, $secondUser->avatar);
        Storage::disk('public')->assertExists('users/first.jpg');
        $this->assertSame(['users/first.jpg'], Storage::disk('public')->allFiles('users'));
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

    public function test_user_sanitizes_preserved_original_file_name_when_needed(): void
    {
        Storage::fake('public');

        $user = $this->uploadedAvatarUser();
        $user->fill($this->userAttributes([
            'avatar' => UploadedFile::fake()->image('../Avatar Final?.JPG'),
        ]));
        $user->save();

        $this->assertSame('users/Avatar-Final.jpg', $user->avatar);
        Storage::disk('public')->assertExists('users/Avatar-Final.jpg');
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
        return new class extends User
        {
            protected $table = 'users';

            protected function fileUploads(): array
            {
                return [
                    'avatar' => [],
                ];
            }
        };
    }

    private function base64AvatarUser(): User
    {
        return new class extends User
        {
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
