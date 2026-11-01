<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha de auditoria das transações.
 *
 * Origem: tabelas `historico`, `just_ed` (justificativa da edição) e
 * `exclusoes` (log de exclusões) do Financas-master — unificadas numa única
 * tabela com o diff (antes/depois) em JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('user_id');
            $table->string('action', 20); // created, updated, paid, unpaid, deleted, restored
            $table->json('changes')->nullable(); // {"campo": {"old": x, "new": y}}
            $table->string('reason', 255)->nullable(); // justificativa opcional
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['transaction_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
