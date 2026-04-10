<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_author_cannot_attach_another_owners_shareable_page(): void
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
            ->assertForbidden();

        $this->assertDatabaseMissing('handbook_page_positions', [
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
        ]);
    }
}
