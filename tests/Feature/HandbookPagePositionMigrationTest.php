<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HandbookPagePositionMigrationTest extends TestCase
{
    public function test_phase_one_schema_adds_shareable_flag_and_positions_table(): void
    {
        Artisan::call('migrate:fresh', [
            '--database' => config('database.default'),
            '--force' => true,
        ]);

        $this->assertTrue(Schema::hasColumn('handbook_pages', 'is_shareable'));
        $this->assertTrue(Schema::hasTable('handbook_page_positions'));
        $this->assertTrue(Schema::hasColumns('handbook_page_positions', [
            'handbook_id',
            'handbook_page_id',
            'position',
        ]));
    }

    public function test_phase_one_migrations_backfill_existing_pages_into_positions(): void
    {
        $this->runMigrations([
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_04_04_121833_create_handbooks_table.php',
            'database/migrations/2026_04_04_121834_create_handbook_pages_table.php',
            'database/migrations/2026_04_09_204312_add_is_listed_to_handbooks_table.php',
        ], fresh: true);

        $handbookId = DB::table('handbooks')->insertGetId([
            'title' => 'Operations',
            'slug' => 'operations',
            'description' => null,
            'is_listed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstPageId = DB::table('handbook_pages')->insertGetId([
            'handbook_id' => $handbookId,
            'title' => 'Intro',
            'slug' => 'intro',
            'position' => 0,
            'body' => '# Intro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondPageId = DB::table('handbook_pages')->insertGetId([
            'handbook_id' => $handbookId,
            'title' => 'Policy',
            'slug' => 'policy',
            'position' => 1,
            'body' => '# Policy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runMigrations([
            'database/migrations/2026_04_10_113650_add_is_shareable_to_handbook_pages_table.php',
            'database/migrations/2026_04_10_113650_create_handbook_page_positions_table.php',
        ]);

        $this->assertTrue(Schema::hasColumn('handbook_pages', 'is_shareable'));
        $this->assertDatabaseHas('handbook_pages', [
            'id' => $firstPageId,
            'is_shareable' => false,
        ]);
        $this->assertDatabaseHas('handbook_pages', [
            'id' => $secondPageId,
            'is_shareable' => false,
        ]);

        $positions = DB::table('handbook_page_positions')
            ->orderBy('position')
            ->get(['handbook_id', 'handbook_page_id', 'position']);

        $this->assertCount(2, $positions);
        $this->assertSame($handbookId, $positions[0]->handbook_id);
        $this->assertSame($firstPageId, $positions[0]->handbook_page_id);
        $this->assertSame(0, $positions[0]->position);
        $this->assertSame($handbookId, $positions[1]->handbook_id);
        $this->assertSame($secondPageId, $positions[1]->handbook_page_id);
        $this->assertSame(1, $positions[1]->position);
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function runMigrations(array $paths, bool $fresh = false): void
    {
        if ($fresh) {
            Artisan::call('db:wipe', [
                '--database' => config('database.default'),
                '--force' => true,
            ]);
        }

        foreach ($paths as $path) {
            Artisan::call('migrate', [
                '--path' => $path,
                '--realpath' => false,
                '--database' => config('database.default'),
                '--force' => true,
            ]);
        }
    }
}
