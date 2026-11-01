<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Published from spatie/laravel-activitylog v5.
 *
 * Deviation from the package stub: users and every domain model use UUID
 * primary keys, so subject and causer are UUID morphs. The table keeps its
 * bigint id and has no SoftDeletes (append-only audit log, pruned by
 * `activitylog:clean`), same exception as the Spatie permission tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableUuidMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableUuidMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
