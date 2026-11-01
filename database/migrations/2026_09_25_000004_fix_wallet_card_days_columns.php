<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correção: `closing_day` e `due_day` foram criadas como DATE, mas o formulário
 * e o StoreWalletRequest tratam o campo como dia do mês (inteiro 1–31).
 * Em MySQL strict mode isso gerava "Incorrect date value" ao cadastrar cartão.
 *
 * Estratégia segura (sem ->change(), que exige doctrine/dbal em versões antigas
 * e não converte DATE -> inteiro): cria colunas novas, copia o DIA da data
 * antiga (se existir), remove as antigas e renomeia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->unsignedTinyInteger('closing_day_tmp')->nullable();
            $table->unsignedTinyInteger('due_day_tmp')->nullable();
        });

        DB::table('wallets')
            ->where(fn ($q) => $q->whereNotNull('closing_day')->orWhereNotNull('due_day'))
            ->orderBy('id')
            ->each(function ($wallet) {
                DB::table('wallets')->where('id', $wallet->id)->update([
                    'closing_day_tmp' => $wallet->closing_day ? (int) date('j', strtotime($wallet->closing_day)) : null,
                    'due_day_tmp' => $wallet->due_day ? (int) date('j', strtotime($wallet->due_day)) : null,
                ]);
            });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['closing_day', 'due_day']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->renameColumn('closing_day_tmp', 'closing_day');
            $table->renameColumn('due_day_tmp', 'due_day');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['closing_day', 'due_day']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->date('closing_day')->nullable();
            $table->date('due_day')->nullable();
        });
    }
};
