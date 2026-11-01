<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive billing sync fields: cancel-at-period-end flag, invoice receipt
 * URLs, and a table that makes Stripe webhook processing idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('cancel_at_period_end')->default(false)->after('current_period_end');
        });

        Schema::table('payment_histories', function (Blueprint $table) {
            $table->text('hosted_invoice_url')->nullable();
            $table->text('invoice_pdf_url')->nullable();
            $table->index('stripe_invoice_id');
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_event_id')->unique();
            $table->string('type')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');

        Schema::table('payment_histories', function (Blueprint $table) {
            $table->dropIndex(['stripe_invoice_id']);
            $table->dropColumn(['hosted_invoice_url', 'invoice_pdf_url']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('cancel_at_period_end');
        });
    }
};
