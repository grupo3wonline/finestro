<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Services\DataExportServiceInterface;
use Illuminate\Console\Command;

/**
 * Deletes personal data export files whose download link has expired
 * (storage limitation: LGPD art. 16, GDPR art. 5(1)(e)).
 */
class PurgeExpiredDataExports extends Command
{
    protected $signature = 'privacy:purge-exports';

    protected $description = 'Remove os arquivos de exportação de dados pessoais expirados';

    public function handle(DataExportServiceInterface $exports): int
    {
        $purged = $exports->purgeExpired();
        $stale = $exports->failStale();

        $this->info("{$purged} exportação(ões) expirada(s) removida(s).");
        $this->info("{$stale} exportação(ões) travada(s) marcada(s) como falha.");

        return self::SUCCESS;
    }
}
