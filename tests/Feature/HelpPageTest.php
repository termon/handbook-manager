<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_content_route_renders_inside_the_navbar_layout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('about'));

        $response
            ->assertOk()
            ->assertSee('About');
    }

    public function test_help_page_renders_the_starter_kit_documentation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('help'));

        $response
            ->assertOk()
            ->assertSee('Documentation')
            ->assertSee('Authoring');
    }

    public function test_help_page_renders_authoring_documentation_pages(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $overviewResponse = $this->actingAs($user)->get(route('help', [
            'page' => 'authoring/index',
        ]));

        $imagesResponse = $this->actingAs($user)->get(route('help', [
            'page' => 'authoring/handbook-images',
        ]));

        $overviewResponse
            ->assertOk()
            ->assertSeeText('Authoring Overview')
            ->assertSeeText('Markdown editor')
            ->assertSeeText('Copy markdown')
            ->assertSeeText('discards unsaved');

        $imagesResponse
            ->assertOk()
            ->assertSeeText('Handbook Images')
            ->assertSeeText('Single image upload')
            ->assertSeeText('Copy markdown')
            ->assertSeeText('discarded');
    }
}
