<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Published from spatie/laravel-medialibrary v11.
 *
 * Deviation from the package stub: every domain model uses UUID primary
 * keys, so the owning model is a UUID morph. The table keeps its bigint id
 * and has no SoftDeletes (the package deletes media rows only when the owner
 * is force-deleted), same exception as the Spatie permission tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->uuidMorphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
