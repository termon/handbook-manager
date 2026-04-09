<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandbookViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_handbook_page_renders_without_flux_markup(): void
    {
        /** @var Handbook $handbook */
        $handbook = Handbook::factory()->create([
            'title' => 'Operations Handbook',
            'slug' => 'operations-handbook',
        ]);

        /** @var HandbookPage $page */
        $page = HandbookPage::factory()->for($handbook)->create([
            'title' => 'Introduction',
            'slug' => 'introduction',
        ]);

        $response = $this->get(route('handbooks.show', [
            'handbook' => $handbook,
            'page' => $page,
        ]));

        $response->assertOk();
        $response->assertSee('Operations Handbook');
        $response->assertSee('Introduction');
        $this->assertStringNotContainsString('<flux:', $response->getContent());
    }

    public function test_admin_handbook_views_render_without_flux_markup(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var Handbook $handbook */
        $handbook = Handbook::factory()->create([
            'title' => 'Support Handbook',
        ]);

        HandbookPage::factory()->for($handbook)->create([
            'title' => 'Start Here',
            'slug' => 'start-here',
        ]);

        $createResponse = $this->actingAs($admin)->get(route('admin.handbooks.create'));
        $indexResponse = $this->actingAs($admin)->get(route('admin.handbooks.index'));
        $editResponse = $this->actingAs($admin)->get(route('admin.handbooks.edit', $handbook));
        $detailsResponse = $this->actingAs($admin)->get(route('admin.handbooks.edit', $handbook).'?panel=details');

        $createResponse->assertOk();
        $createResponse->assertSee('Create handbook');
        $this->assertStringNotContainsString('<flux:', $createResponse->getContent());

        $indexResponse->assertOk();
        $indexResponse->assertSee('Handbook manager');
        $indexResponse->assertSee('Support Handbook');
        $this->assertStringNotContainsString('<flux:', $indexResponse->getContent());

        $editResponse->assertOk();
        $editResponse->assertSee('Markdown editor');
        $editResponse->assertSee('Pages');
        $this->assertStringNotContainsString('<flux:', $editResponse->getContent());

        $detailsResponse->assertOk();
        $detailsResponse->assertSee('Handbook details');
        $detailsResponse->assertSee('Owner');
        $this->assertStringNotContainsString('<flux:', $detailsResponse->getContent());
    }
}
