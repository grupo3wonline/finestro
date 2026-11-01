<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cookie consent decisions of logged-in users (LGPD art. 8 §2 / GDPR art. 7(1):
 * the controller must be able to prove consent). Guests only get the
 * first-party `finestro_consent` cookie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('version', 20);
            $table->boolean('analytics');
            $table->boolean('marketing');
            $table->boolean('gpc')->default(false);
            $table->string('source', 20);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
