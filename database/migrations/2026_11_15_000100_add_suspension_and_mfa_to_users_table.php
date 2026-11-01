<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive admin-panel fields on users: account suspension timestamp and the
 * Filament app (TOTP) multi-factor secret + hashed recovery codes. Both MFA
 * columns are encrypted by the Filament model traits, hence `text`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->index();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['suspended_at', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
