<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handbook_page_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handbook_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handbook_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['handbook_id', 'handbook_page_id']);
            $table->index(['handbook_id', 'position']);
        });

        $timestamp = now();

        $positions = DB::table('handbook_pages')
            ->select([
                'handbook_id',
                'id as handbook_page_id',
                'position',
            ])
            ->orderBy('handbook_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (object $page): array => [
                'handbook_id' => $page->handbook_id,
                'handbook_page_id' => $page->handbook_page_id,
                'position' => $page->position,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($positions !== []) {
            DB::table('handbook_page_positions')->insert($positions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handbook_page_positions');
    }
};
