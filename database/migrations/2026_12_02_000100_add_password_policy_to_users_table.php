<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive: password aging.
 *
 * - password_changed_at: when the current password was set; the password
 *   expires config('security.password_max_age_days') days later.
 * - password_expiry_reminded_at: when the "expires soon" e-mail was sent, so
 *   it goes out once per password (compared with password_changed_at).
 *
 * Existing accounts start their 90-day window on the day of the migration,
 * so nobody is locked out the moment this ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->timestamp('password_expiry_reminded_at')->nullable()->after('password_changed_at');

            $table->index('password_changed_at');
        });

        DB::table('users')->whereNull('password_changed_at')->update(['password_changed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['password_changed_at']);
            $table->dropColumn(['password_changed_at', 'password_expiry_reminded_at']);
        });
    }
};
