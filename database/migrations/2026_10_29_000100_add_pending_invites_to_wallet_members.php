<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow inviting people who do not have an account yet.
 *
 * A pending invite may exist with no user_id: it carries the invited email
 * plus a signed token, and is claimed when the person registers/logs in with
 * that email. Once claimed, user_id is filled and email/token are cleared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_members', function (Blueprint $table) {
            // user_id becomes nullable: unknown until the invite is claimed.
            $table->foreignUuid('user_id')->nullable()->change();

            $table->string('email')->nullable()->after('user_id');
            $table->string('invitation_token', 64)->nullable()->unique()->after('role');
            $table->timestamp('expires_at')->nullable()->after('accepted_at');

            // Speeds up "pending invites for this email" lookups on registration.
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_members', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropUnique(['invitation_token']);
            $table->dropColumn(['email', 'invitation_token', 'expires_at']);
            // Note: user_id is left nullable on rollback to avoid failing when
            // unclaimed invites exist. Re-add NOT NULL manually if required.
        });
    }
};
