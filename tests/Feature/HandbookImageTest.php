<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookImage;
use App\Support\HandbookMarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HandbookImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_handbook_image_can_store_uploaded_file_as_base64(): void
    {
        $handbook = Handbook::factory()->create();
        $upload = UploadedFile::fake()->image('My Image?.PNG');

        $image = new HandbookImage;
        $image->handbook()->associate($handbook);
        $image->fill([
            'handbook_id' => $handbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => HandbookImage::sanitizedUploadName($upload),
            'alt_text' => 'My Image?',
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        $this->assertStringStartsWith('data:image/', $image->path);
        $this->assertSame('My-Image.png', $image->name);
        $this->assertSame($image->path, $image->relativeUrl());
        $this->assertSame('![My Image?](My-Image.png)', $image->markdownSnippet());
    }

    public function test_handbook_markdown_renderer_replaces_image_name_with_base64_source(): void
    {
        $handbook = Handbook::factory()->create();
        $upload = UploadedFile::fake()->image('diagram.jpg');

        $image = new HandbookImage;
        $image->handbook()->associate($handbook);
        $image->fill([
            'handbook_id' => $handbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => HandbookImage::sanitizedUploadName($upload),
            'alt_text' => 'diagram',
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        $html = app(HandbookMarkdownRenderer::class)->render(
            $handbook->load('images'),
            '![diagram](diagram.jpg)',
        );

        $this->assertStringContainsString('src="'.$image->path.'"', $html);
    }

    public function test_handbook_image_delete_still_succeeds_for_base64_content(): void
    {
        $handbook = Handbook::factory()->create();
        $upload = UploadedFile::fake()->image('diagram.jpg');

        $image = new HandbookImage;
        $image->handbook()->associate($handbook);
        $image->fill([
            'handbook_id' => $handbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => HandbookImage::sanitizedUploadName($upload),
            'alt_text' => 'diagram',
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        $image->delete();

        $this->assertDatabaseMissing('handbook_images', [
            'id' => $image->id,
        ]);
    }
}
