<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

/**
 * Copies legacy receipts (transactions.receipt_path) into the media library.
 *
 * Idempotent and non-destructive: rows that already have receipt media are
 * skipped, the source file is preserved and receipt_path is never changed,
 * so the command can be re-run safely. Soft-deleted transactions are
 * included (their receipt comes back if the row is restored).
 */
class ImportReceiptsToMedia extends Command
{
    protected $signature = 'receipts:import-media
                            {--dry-run : Apenas mostra o que seria importado}';

    protected $description = 'Importa os comprovantes antigos (receipt_path) para a biblioteca de mídia, sem apagar os arquivos originais';

    private const string DISK = 'private';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $imported = 0;
        $alreadyImported = 0;
        $missing = 0;
        $failed = 0;

        // No user scope and no SoftDeletes scope: runs in the console over every row.
        Transaction::withoutGlobalScopes()
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->with('media')
            ->chunkById(100, function (Collection $transactions) use ($dryRun, &$imported, &$alreadyImported, &$missing, &$failed): void {
                /** @var Transaction $transaction */
                foreach ($transactions as $transaction) {
                    $path = (string) $transaction->receipt_path;

                    if ($transaction->hasMedia(Transaction::RECEIPT_COLLECTION)) {
                        $alreadyImported++;

                        continue;
                    }

                    if (! Storage::disk(self::DISK)->exists($path)) {
                        $missing++;
                        $this->warn("Arquivo ausente: transação {$transaction->id} ({$path})");

                        continue;
                    }

                    if ($dryRun) {
                        $imported++;
                        $this->line("Seria importado: transação {$transaction->id} ({$path})");

                        continue;
                    }

                    try {
                        $transaction->addMediaFromDisk($path, self::DISK)
                            ->preservingOriginal()
                            ->usingFileName(basename($path))
                            ->toMediaCollection(Transaction::RECEIPT_COLLECTION, self::DISK);
                        $imported++;
                    } catch (FileCannotBeAdded $e) {
                        $failed++;
                        $this->error("Não foi possível importar a transação {$transaction->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info(($dryRun ? '[simulação] ' : '')."Importados: {$imported} | Já importados: {$alreadyImported} | Arquivo ausente: {$missing} | Com erro: {$failed}");

        return self::SUCCESS;
    }
}
