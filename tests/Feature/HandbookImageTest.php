<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookImage;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Support\HandbookMarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $page = HandbookPage::factory()->for($handbook)->create([
            'body' => '![diagram](diagram.jpg)',
        ]);
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
            $page->setRelation('handbook', $handbook->load('images')),
        );

        $this->assertStringContainsString('src="'.$image->path.'"', $html);
    }

    public function test_handbook_markdown_renderer_replaces_image_name_with_file_source(): void
    {
        Storage::fake('public');

        $handbook = Handbook::factory()->create();
        $page = HandbookPage::factory()->for($handbook)->create([
            'body' => '![physical file](physical-file.jpg)',
        ]);
        $upload = UploadedFile::fake()->image('physical-file.jpg');

        $image = new class extends HandbookImage
        {
            protected $table = 'handbook_images';

            protected function fileUploads(): array
            {
                $handbookId = $this->attributes['handbook_id'] ?? $this->getRawOriginal('handbook_id');

                return [
                    'path' => [
                        'disk' => 'public',
                        'folder' => "handbooks/{$handbookId}/images",
                    ],
                ];
            }
        };

        $image->handbook()->associate($handbook);
        $image->fill([
            'handbook_id' => $handbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => HandbookImage::sanitizedUploadName($upload),
            'alt_text' => 'physical file',
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        $html = app(HandbookMarkdownRenderer::class)->render(
            $handbook->load('images'),
            $page->setRelation('handbook', $handbook->load('images')),
        );

        $this->assertSame("handbooks/{$handbook->id}/images/physical-file.jpg", $image->path);
        Storage::disk('public')->assertExists($image->path);
        $this->assertStringContainsString('src="/storage/'.$image->path.'"', $html);
        $this->assertSame('![physical file](physical-file.jpg)', $image->markdownSnippet());
    }

    public function test_handbook_markdown_renderer_uses_source_handbook_images_for_shared_pages(): void
    {
        $sourceHandbook = Handbook::factory()->create();
        $consumerHandbook = Handbook::factory()->create();
        $page = HandbookPage::factory()->for($sourceHandbook)->create([
            'body' => '![diagram](diagram.jpg)',
        ]);
        $upload = UploadedFile::fake()->image('diagram.jpg');

        $image = new HandbookImage;
        $image->handbook()->associate($sourceHandbook);
        $image->fill([
            'handbook_id' => $sourceHandbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => HandbookImage::sanitizedUploadName($upload),
            'alt_text' => 'diagram',
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        $html = app(HandbookMarkdownRenderer::class)->render(
            $consumerHandbook,
            $page->setRelation('handbook', $sourceHandbook->load('images')),
        );

        $this->assertStringContainsString('src="'.$image->path.'"', $html);
    }

    public function test_handbook_markdown_renderer_prefers_display_handbook_for_relative_page_links(): void
    {
        $sourceHandbook = Handbook::factory()->create([
            'slug' => 'source-handbook',
        ]);
        $consumerHandbook = Handbook::factory()->create([
            'slug' => 'consumer-handbook',
        ]);
        $page = HandbookPage::factory()->for($sourceHandbook)->create([
            'body' => '[Policy](policy)',
        ]);
        $consumerTarget = HandbookPage::factory()->for($consumerHandbook)->create([
            'slug' => 'policy',
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $page->id,
            'position' => 1,
        ]);

        $html = app(HandbookMarkdownRenderer::class)->render(
            $consumerHandbook,
            $page->setRelation('handbook', $sourceHandbook),
        );

        $this->assertStringContainsString(route('handbooks.show', [
            'handbook' => $consumerHandbook,
            'pageSlug' => $consumerTarget->slug,
        ], false), $html);
    }

    public function test_handbook_markdown_renderer_falls_back_to_source_handbook_for_missing_relative_page_links(): void
    {
        $sourceHandbook = Handbook::factory()->create([
            'slug' => 'source-handbook',
        ]);
        $consumerHandbook = Handbook::factory()->create([
            'slug' => 'consumer-handbook',
        ]);
        $page = HandbookPage::factory()->for($sourceHandbook)->create([
            'body' => '[Policy](policy)',
        ]);
        $sourceTarget = HandbookPage::factory()->for($sourceHandbook)->create([
            'slug' => 'policy',
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $page->id,
            'position' => 1,
        ]);

        $html = app(HandbookMarkdownRenderer::class)->render(
            $consumerHandbook,
            $page->setRelation('handbook', $sourceHandbook),
        );

        $this->assertStringContainsString(route('handbooks.show', [
            'handbook' => $sourceHandbook,
            'pageSlug' => $sourceTarget->slug,
        ], false), $html);
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
