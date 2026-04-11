<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class HandbookAdminEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_edit_view_marks_shared_pages_as_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $sourceHandbook = Handbook::factory()->create([
            'title' => 'Source Handbook',
        ]);
        $consumerHandbook = Handbook::factory()->create([
            'title' => 'Consumer Handbook',
        ]);

        HandbookPage::factory()->for($consumerHandbook)->create([
            'title' => 'Consumer Start',
            'slug' => 'consumer-start',
        ]);

        $sharedPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Shared Policy',
            'slug' => 'shared-policy',
            'is_shareable' => true,
        ]);

        $sharedPosition = HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.handbooks.edit', $consumerHandbook).'?page='.$sharedPosition->id
        );

        $response->assertOk();
        $response->assertSee("This page is shared from {$sourceHandbook->title} and can't be edited here.", false);
        $response->assertSee('Read only');
        $response->assertSee('Shared');
    }

    public function test_admin_can_open_the_preview_panel_for_a_selected_position(): void
    {
        $admin = User::factory()->admin()->create();
        $handbook = Handbook::factory()->create([
            'title' => 'Preview Handbook',
        ]);

        $page = HandbookPage::factory()->for($handbook)->create([
            'title' => 'Previewable Page',
            'slug' => 'previewable-page',
            'body' => '# Previewable Page',
        ]);

        $positionId = (int) $page->positions()
            ->where('handbook_id', $handbook->id)
            ->value('id');

        $response = $this->actingAs($admin)->get(
            route('admin.handbooks.edit', $handbook).'?panel=preview&page='.$positionId
        );

        $response->assertOk();
        $response->assertSee('Page preview');
        $response->assertSee('Previewable Page');
    }

    public function test_images_panel_disables_upload_actions_until_files_are_ready(): void
    {
        $admin = User::factory()->admin()->create();
        $handbook = Handbook::factory()->create([
            'title' => 'Image Handbook',
        ]);

        HandbookPage::factory()->for($handbook)->create([
            'title' => 'Gallery',
            'slug' => 'gallery',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.handbooks.edit', $handbook).'?panel=images'
        );

        $response->assertOk();

        $crawler = new Crawler($response->getContent());

        $singleUploadButton = $crawler->filterXPath('//button[contains(normalize-space(.), "Upload image")]')->first();
        $multiUploadButton = $crawler->filterXPath('//button[contains(normalize-space(.), "Upload images")]')->first();

        $this->assertSame('! hasSelectedFile', $singleUploadButton->attr('x-bind:disabled'));
        $this->assertSame('imageUpload,uploadImage', $singleUploadButton->attr('wire:target'));
        $this->assertSame('uploading || ! hasSelectedFiles()', $multiUploadButton->attr('x-bind:disabled'));
    }

    public function test_saving_handbook_details_redirects_to_the_updated_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $handbook = Handbook::factory()->create([
            'title' => 'Original Handbook',
            'slug' => 'original-handbook',
        ]);

        $page = HandbookPage::factory()->for($handbook)->create([
            'title' => 'Intro',
            'slug' => 'intro',
        ]);

        $positionId = (int) $page->positions()
            ->where('handbook_id', $handbook->id)
            ->value('id');

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.edit', ['handbook' => $handbook])
            ->set('panel', 'details')
            ->set('selectedPositionId', $positionId)
            ->set('handbookTitle', 'Renamed Handbook')
            ->call('saveHandbook')
            ->assertRedirect(route('admin.handbooks.edit', $handbook->fresh(), absolute: false).'?panel=details&page='.$positionId);

        $this->assertDatabaseHas('handbooks', [
            'id' => $handbook->id,
            'title' => 'Renamed Handbook',
            'slug' => 'renamed-handbook',
        ]);
    }

    public function test_admin_can_attach_a_shareable_page_to_a_handbook(): void
    {
        $admin = User::factory()->admin()->create();
        $sourceHandbook = Handbook::factory()->create();
        $consumerHandbook = Handbook::factory()->create();

        HandbookPage::factory()->for($consumerHandbook)->create([
            'title' => 'Consumer Start',
        ]);

        $sharedPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Reusable Policy',
            'slug' => 'reusable-policy',
            'is_shareable' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.edit', ['handbook' => $consumerHandbook])
            ->call('beginAddSharedPage')
            ->set('selectedSharedPageId', (string) $sharedPage->id)
            ->call('attachSharedPage')
            ->assertSet('selectedPositionId', fn ($value) => is_int($value) && $value > 0)
            ->assertSet('pageTitle', 'Reusable Policy')
            ->assertSet('pageIsShareable', true);

        $this->assertDatabaseHas('handbook_page_positions', [
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
        ]);
    }

    public function test_shareable_pages_can_be_filtered_by_page_title_and_source_handbook(): void
    {
        $admin = User::factory()->admin()->create();
        $sourceHandbook = Handbook::factory()->create([
            'title' => 'Policies',
        ]);
        $otherSourceHandbook = Handbook::factory()->create([
            'title' => 'Operations',
        ]);
        $consumerHandbook = Handbook::factory()->create();

        HandbookPage::factory()->for($consumerHandbook)->create([
            'title' => 'Consumer Start',
        ]);

        $policyPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Leave Policy',
            'is_shareable' => true,
        ]);
        $operationsPage = HandbookPage::factory()->for($otherSourceHandbook)->create([
            'title' => 'Incident Guide',
            'is_shareable' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.edit', ['handbook' => $consumerHandbook])
            ->call('beginAddSharedPage')
            ->set('sharedPageSearch', 'Policy')
            ->assertSee($policyPage->title)
            ->assertDontSee($operationsPage->title)
            ->set('sharedPageSearch', 'Operations')
            ->assertSee($operationsPage->title)
            ->assertDontSee($policyPage->title);
    }

    public function test_author_can_attach_another_owners_shareable_page(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();
        $consumerHandbook = Handbook::factory()->for($author, 'owner')->create();
        $otherSourceHandbook = Handbook::factory()->for($otherAuthor, 'owner')->create();

        HandbookPage::factory()->for($consumerHandbook)->create([
            'title' => 'Consumer Start',
        ]);

        $sharedPage = HandbookPage::factory()->for($otherSourceHandbook)->create([
            'title' => 'Other Author Shared Page',
            'is_shareable' => true,
        ]);

        $this->actingAs($author);

        Livewire::test('pages::admin.handbooks.edit', ['handbook' => $consumerHandbook])
            ->call('beginAddSharedPage')
            ->set('selectedSharedPageId', (string) $sharedPage->id)
            ->call('attachSharedPage')
            ->assertSet('selectedPositionId', fn ($value) => is_int($value) && $value > 0)
            ->assertSet('pageTitle', 'Other Author Shared Page')
            ->assertSet('pageIsShareable', true);

        $this->assertDatabaseHas('handbook_page_positions', [
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
        ]);
    }

    public function test_author_edit_view_lists_shared_pages_from_other_authors(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();
        $sourceHandbook = Handbook::factory()->for($otherAuthor, 'owner')->create([
            'title' => 'Shared Source Handbook',
        ]);
        $consumerHandbook = Handbook::factory()->for($author, 'owner')->create([
            'title' => 'Author Handbook',
        ]);

        HandbookPage::factory()->for($consumerHandbook)->create([
            'title' => 'Consumer Start',
            'slug' => 'consumer-start',
        ]);

        $sharedPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Shared Incident Playbook',
            'slug' => 'shared-incident-playbook',
            'is_shareable' => true,
        ]);

        $sharedPosition = HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $response = $this->actingAs($author)->get(
            route('admin.handbooks.edit', $consumerHandbook).'?page='.$sharedPosition->id
        );

        $response->assertOk();
        $response->assertSee('Shared Incident Playbook');
        $response->assertSee('From Shared Source Handbook');
        $response->assertSee("This page is shared from {$sourceHandbook->title} and can't be edited here.", false);
    }
}
