<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handbook_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handbook_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->mediumtext('path');
            $table->string('name');
           
            $table->string('alt_text')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->unique(['handbook_id', 'name']);
            $table->index(['handbook_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handbook_images');
    }
};
