<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orçamentos mensais (teto de gastos).
 *
 * Origem: tabela `orcamento` do Financas-master (mês/ano/valor por usuário).
 * Evolução: orçamento opcionalmente por categoria (category_id NULL = orçamento
 * geral do mês) e percentual de alerta configurável.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('category_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 15, 2);
            $table->unsignedTinyInteger('alert_threshold')->default(80); // % para alertar
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
