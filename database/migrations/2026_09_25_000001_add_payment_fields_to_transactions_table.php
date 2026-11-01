<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contas a pagar/receber, forma de pagamento e parcelamento.
 *
 * Origem das ideias (bkp-after_features):
 *  - ebfinance: coluna `pago` (s/n) + `forma_pagamento`
 *  - Financas-master: colunas `nparcela` / `parcelas`
 *
 * `recurring_source_id` identifica a série de uma transação recorrente, para o
 * agendador gerar a próxima ocorrência a partir da ÚLTIMA da série (e não de
 * todas elas, o que causava risco de duplicidade).
 *
 * Todas as transações já existentes são marcadas como "paid", preservando
 * exatamente o saldo atual das carteiras (antes desta migração toda transação
 * afetava o saldo imediatamente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('paid')->after('type');           // paid | pending
            $table->date('due_date')->nullable()->after('date');                    // vencimento
            $table->timestamp('paid_at')->nullable()->after('due_date');            // quando foi pago/recebido
            $table->string('payment_method', 30)->nullable()->after('paid_at');     // pix, boleto, credit_card...
            $table->uuid('recurring_source_id')->nullable()->after('recurring_frequency'); // série da recorrência
            $table->uuid('installment_group_id')->nullable()->after('recurring_source_id');
            $table->unsignedSmallInteger('installment_number')->nullable()->after('installment_group_id');
            $table->unsignedSmallInteger('installment_total')->nullable()->after('installment_number');
            $table->string('origin', 30)->nullable()->after('installment_total'); // ex.: invoice_payment

            $table->index(['user_id', 'status', 'due_date']);
            $table->index('installment_group_id');
            $table->index('recurring_source_id');
        });

        // Backfill: o que já existe foi efetivamente pago na data da transação.
        DB::table('transactions')->whereNull('paid_at')->update([
            'paid_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'due_date']);
            $table->dropIndex(['installment_group_id']);
            $table->dropIndex(['recurring_source_id']);
            $table->dropColumn([
                'status',
                'due_date',
                'paid_at',
                'payment_method',
                'recurring_source_id',
                'installment_group_id',
                'installment_number',
                'installment_total',
                'origin',
            ]);
        });
    }
};
