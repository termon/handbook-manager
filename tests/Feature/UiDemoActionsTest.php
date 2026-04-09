<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiDemoActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_demo_preview_post_requires_authentication(): void
    {
        $response = $this->post(route('ui-demo.preview.post'));

        $response->assertRedirect(route('ui-demo'));
    }

    public function test_ui_demo_preview_post_redirects_back_with_success_flash(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ui-demo.preview.post'));

        $response
            ->assertRedirect(route('ui-demo'))
            ->assertSessionHas('success', 'Demo POST action submitted.');
    }

    public function test_ui_demo_preview_patch_redirects_back_with_status_flash(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('ui-demo.preview.patch'));

        $response
            ->assertRedirect(route('ui-demo'))
            ->assertSessionHas('status', 'Demo PATCH action submitted.');
    }

    public function test_ui_demo_preview_delete_redirects_back_with_warning_flash(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('ui-demo.preview.delete'));

        $response
            ->assertRedirect(route('ui-demo'))
            ->assertSessionHas('warning', 'Demo DELETE action submitted.');
    }
}
