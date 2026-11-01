<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataExportStatus;
use App\Exceptions\DataExportException;
use App\Exports\Concerns\EscapesFormulaInjection;
use App\Interfaces\Repositories\DataExportRepositoryInterface;
use App\Interfaces\Repositories\PersonalDataRepositoryInterface;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Jobs\GeneratePersonalDataExport;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DataExportService implements DataExportServiceInterface
{
    use EscapesFormulaInjection;

    public function __construct(
        protected DataExportRepositoryInterface $exports,
        protected PersonalDataRepositoryInterface $personalData,
        protected SecurityActivityLoggerInterface $security,
    ) {}

    public function request(User $user): DataExport
    {
        if ($this->exports->hasPending((string) $user->id)) {
            throw new DataExportException('Já existe uma exportação em preparação. Aguarde alguns minutos e atualize a página.');
        }

        $export = $this->exports->create((string) $user->id, (string) config('privacy.export_disk'));

        $this->security->log('data_export_requested', $user, ['export_id' => $export->id]);

        GeneratePersonalDataExport::dispatch((string) $export->id);

        return $export->fresh() ?? $export;
    }

    public function build(DataExport $export): void
    {
        $tmp = null;

        try {
            $user = User::withTrashed()->findOrFail($export->user_id);
            $sections = $this->personalData->collect($user);

            $tmp = (string) tempnam(sys_get_temp_dir(), 'finestro-export-');
            $zip = new ZipArchive;

            if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create the export archive.');
            }

            $zip->addFromString('data.json', json_encode([
                'exported_at' => now()->toIso8601String(),
                'policy_version' => config('legal.policy_version'),
                'sections' => $sections,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            foreach ($sections as $name => $rows) {
                $zip->addFromString($name.'.csv', $this->toCsv($rows));
            }

            $zip->close();

            $path = sprintf('exports/%s/%s.zip', $export->user_id, $export->id);
            $size = (int) filesize($tmp);
            $stream = fopen($tmp, 'rb');

            if ($stream === false || ! Storage::disk($export->disk)->writeStream($path, $stream)) {
                throw new \RuntimeException('Could not store the export archive.');
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->exports->markReady($export, $path, $size, now()->addHours((int) config('privacy.export_ttl_hours')));
        } catch (\Throwable $e) {
            // Ids and the exception class only: never log personal data.
            Log::error('Personal data export failed', [
                'export_id' => $export->id,
                'exception' => $e::class,
            ]);
            $this->exports->markFailed($export);
        } finally {
            if ($tmp !== null && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    public function downloadUrl(DataExport $export): string
    {
        return URL::temporarySignedRoute(
            'settings.privacy.download',
            $export->expires_at ?? now(),
            ['export' => $export->id],
        );
    }

    public function download(DataExport $export, User $user): StreamedResponse
    {
        abort_unless($export->user_id === $user->id, 404);
        abort_if($export->isExpired(), 410, 'Este link de exportação expirou. Solicite uma nova exportação.');
        abort_unless($export->isDownloadable() && $export->path !== null, 404);

        $disk = Storage::disk($export->disk);
        abort_unless($disk->exists($export->path), 410, 'Este arquivo não está mais disponível. Solicite uma nova exportação.');

        $this->security->log('data_export_downloaded', $user, ['export_id' => $export->id]);

        return $disk->download($export->path, 'finestro-meus-dados-'.$export->created_at->format('Y-m-d').'.zip');
    }

    public function purgeExpired(): int
    {
        $purged = 0;

        foreach ($this->exports->expired() as $export) {
            if ($export->path !== null) {
                Storage::disk($export->disk)->delete($export->path);
            }

            $this->exports->markExpired($export);
            $purged++;
        }

        return $purged;
    }

    public function failStale(): int
    {
        $failed = 0;

        foreach ($this->exports->stalePending() as $export) {
            $this->exports->markFailed($export);
            $failed++;
        }

        return $failed;
    }

    public function markFailedIfPending(string $exportId): void
    {
        $export = $this->exports->find($exportId);

        if ($export instanceof DataExport && $export->status === DataExportStatus::Pending) {
            $this->exports->markFailed($export);
        }
    }

    protected function writerType(): string
    {
        return Excel::CSV;
    }

    /**
     * UTF-8 CSV (with BOM for spreadsheet apps) with formula-escaped cells.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Could not open a temporary CSV buffer.');
        }

        fwrite($handle, "\xEF\xBB\xBF");

        if ($rows !== []) {
            fputcsv($handle, array_keys($rows[0]), ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($handle, array_map($this->cell(...), array_values($row)), ',', '"', '');
            }
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function cell(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            is_scalar($value) => $this->text($value),
            default => $this->text((string) json_encode($value, JSON_UNESCAPED_UNICODE)),
        };
    }
}
