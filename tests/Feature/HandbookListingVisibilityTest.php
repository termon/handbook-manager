<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandbookListingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_only_sees_listed_handbooks_on_public_index(): void
    {
        $listed = Handbook::factory()->create(['title' => 'Listed Handbook', 'is_listed' => true]);
        $unlisted = Handbook::factory()->unlisted()->create(['title' => 'Unlisted Handbook']);

        HandbookPage::factory()->for($listed)->create();
        HandbookPage::factory()->for($unlisted)->create();

        $response = $this->get(route('handbooks.index'));

        $response->assertOk();
        $response->assertSee('Listed Handbook');
        $response->assertDontSee('Unlisted Handbook');
    }

    public function test_admin_sees_all_handbooks_on_public_index(): void
    {
        $admin = User::factory()->admin()->create();

        $listed = Handbook::factory()->create(['title' => 'Listed Handbook', 'is_listed' => true]);
        $unlisted = Handbook::factory()->unlisted()->create(['title' => 'Unlisted Handbook']);

        HandbookPage::factory()->for($listed)->create();
        HandbookPage::factory()->for($unlisted)->create();

        $response = $this->actingAs($admin)->get(route('handbooks.index'));

        $response->assertOk();
        $response->assertSee('Listed Handbook');
        $response->assertSee('Unlisted Handbook');
    }

    public function test_author_sees_listed_handbooks_plus_their_own_unlisted_handbooks(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();

        $listed = Handbook::factory()->for($otherAuthor, 'owner')->create(['title' => 'Listed Handbook', 'is_listed' => true]);
        $ownedUnlisted = Handbook::factory()->unlisted()->for($author, 'owner')->create(['title' => 'Owned Unlisted Handbook']);
        $otherUnlisted = Handbook::factory()->unlisted()->for($otherAuthor, 'owner')->create(['title' => 'Other Unlisted Handbook']);

        HandbookPage::factory()->for($listed)->create();
        HandbookPage::factory()->for($ownedUnlisted)->create();
        HandbookPage::factory()->for($otherUnlisted)->create();

        $response = $this->actingAs($author)->get(route('handbooks.index'));

        $response->assertOk();
        $response->assertSee('Listed Handbook');
        $response->assertSee('Owned Unlisted Handbook');
        $response->assertDontSee('Other Unlisted Handbook');
    }

    public function test_authenticated_non_author_only_sees_listed_handbooks_on_public_index(): void
    {
        $user = User::factory()->create();

        $listed = Handbook::factory()->create(['title' => 'Listed Handbook', 'is_listed' => true]);
        $unlisted = Handbook::factory()->unlisted()->create(['title' => 'Unlisted Handbook']);

        HandbookPage::factory()->for($listed)->create();
        HandbookPage::factory()->for($unlisted)->create();

        $response = $this->actingAs($user)->get(route('handbooks.index'));

        $response->assertOk();
        $response->assertSee('Listed Handbook');
        $response->assertDontSee('Unlisted Handbook');
    }

    public function test_unlisted_handbook_is_still_accessible_by_direct_url(): void
    {
        $handbook = Handbook::factory()->unlisted()->create([
            'title' => 'Direct Link Handbook',
            'slug' => 'direct-link-handbook',
        ]);

        $page = HandbookPage::factory()->for($handbook)->create([
            'title' => 'Introduction',
            'slug' => 'introduction',
        ]);

        $response = $this->get(route('handbooks.show', [
            'handbook' => $handbook,
            'pageSlug' => $page->slug,
        ]));

        $response->assertOk();
        $response->assertSee('Direct Link Handbook');
        $response->assertSee('Introduction');
    }
}
