<?php

namespace Tests\Feature;

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandbookPagePositionRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handbook_positions_relation_returns_positions_in_order(): void
    {
        $handbook = Handbook::factory()->create();
        $firstPage = HandbookPage::factory()->for($handbook)->create();
        $secondPage = HandbookPage::factory()->for($handbook)->create();

        $firstPage->positions()->where('handbook_id', $handbook->id)->update(['position' => 1]);
        $secondPage->positions()->where('handbook_id', $handbook->id)->update(['position' => 2]);

        $positions = $handbook->positions()->get();

        $this->assertCount(2, $positions);
        $this->assertSame(1, $positions[0]->position);
        $this->assertTrue($positions[0]->page->is($firstPage));
        $this->assertSame(2, $positions[1]->position);
        $this->assertTrue($positions[1]->page->is($secondPage));
    }

    public function test_handbook_page_helpers_reflect_editability_and_sharing(): void
    {
        $sourceHandbook = Handbook::factory()->create();
        $consumerHandbook = Handbook::factory()->create();
        $page = HandbookPage::factory()->for($sourceHandbook)->create([
            'is_shareable' => true,
        ]);

        $this->assertTrue($page->isEditableIn($sourceHandbook));
        $this->assertFalse($page->isEditableIn($consumerHandbook));
        $this->assertFalse($page->isShared());

        HandbookPagePosition::query()->create([
            'handbook_id' => $consumerHandbook->id,
            'handbook_page_id' => $page->id,
            'position' => 1,
        ]);

        $this->assertTrue($page->fresh()->isShared());
    }
}
