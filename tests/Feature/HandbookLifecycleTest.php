<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HandbookLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_handbook_copies_local_pages_and_preserves_shared_positions(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->author()->create();
        $sourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Operations',
        ]);
        $sharedSourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Shared Source',
        ]);

        $localPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Local Page',
            'slug' => 'local-page',
            'body' => '![Diagram](/storage/handbooks/'.$sourceHandbook->id.'/images/diagram.jpg)',
        ]);

        $sharedPage = HandbookPage::factory()->for($sharedSourceHandbook)->create([
            'title' => 'Shared Page',
            'slug' => 'shared-page',
            'is_shareable' => true,
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $sourceHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.index')
            ->call('beginDuplicate', $sourceHandbook->id)
            ->set('duplicateTitle', 'Operations Copy')
            ->set('duplicateOwnerId', (string) $owner->id)
            ->call('duplicateHandbook');

        $copiedHandbook = Handbook::query()->where('title', 'Operations Copy')->firstOrFail();

        $copiedLocalPage = HandbookPage::query()
            ->where('handbook_id', $copiedHandbook->id)
            ->where('title', 'Local Page')
            ->first();

        $this->assertNotNull($copiedLocalPage);
        $this->assertStringContainsString("/storage/handbooks/{$copiedHandbook->id}/images/", $copiedLocalPage->body);
        $this->assertDatabaseHas('handbook_page_positions', [
            'handbook_id' => $copiedHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);
        $this->assertSame(1, HandbookPage::query()->where('handbook_id', $copiedHandbook->id)->count());
    }

    public function test_delete_handbook_is_blocked_when_it_owns_pages_shared_elsewhere(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->author()->create();
        $sourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Source Handbook',
        ]);
        $consumerHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Consumer Handbook',
        ]);

        $sharedPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Shared Policy',
            'is_shareable' => true,
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.index')
            ->call('confirmDeleteHandbook', $sourceHandbook->id)
            ->call('deleteHandbook')
            ->assertSet('deleteHandbookError', 'This handbook owns pages shared with other handbooks. Remove those shared positions before deleting the handbook.');

        $this->assertDatabaseHas('handbooks', [
            'id' => $sourceHandbook->id,
        ]);
    }

    public function test_duplicate_flow_explains_that_shared_pages_remain_linked(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->author()->create();
        $sourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Operations',
        ]);
        $sharedSourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Policies',
        ]);

        HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Local Page',
        ]);

        $sharedPage = HandbookPage::factory()->for($sharedSourceHandbook)->create([
            'title' => 'Shared Page',
            'is_shareable' => true,
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $sourceHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.index')
            ->call('beginDuplicate', $sourceHandbook->id)
            ->assertSee('shared pages will remain linked to their source handbooks');
    }

    public function test_delete_modal_warns_when_a_handbook_owns_shared_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->author()->create();
        $sourceHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Source Handbook',
        ]);
        $consumerHandbook = Handbook::factory()->for($owner, 'owner')->create([
            'title' => 'Consumer Handbook',
        ]);

        $sharedPage = HandbookPage::factory()->for($sourceHandbook)->create([
            'title' => 'Shared Policy',
            'is_shareable' => true,
        ]);

        HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $sharedPage->id,
            'position' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test('pages::admin.handbooks.index')
            ->call('confirmDeleteHandbook', $sourceHandbook->id)
            ->assertSee('Delete is blocked while this handbook owns pages shared with other handbooks.');
    }
}
