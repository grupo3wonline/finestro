<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add business-specific fields to wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('ownership');
            $table->string('company_document', 20)->nullable()->after('company_name');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('balance');
            $table->date('closing_day')->nullable()->after('credit_limit');
            $table->date('due_day')->nullable()->after('closing_day');
            $table->boolean('is_shared')->default(false)->after('is_active');
        });

        // Wallet sharing - allows business wallets to be accessed by multiple users
        Schema::create('wallet_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['viewer', 'editor', 'admin'])->default('viewer');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['wallet_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_members');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_document',
                'credit_limit',
                'closing_day',
                'due_day',
                'is_shared',
            ]);
        });
    }
};
