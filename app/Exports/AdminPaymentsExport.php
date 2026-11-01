<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\EscapesFormulaInjection;
use App\Interfaces\Services\PlanPricingInterface;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Admin payments export (CSV and XLSX) of the filtered admin table query.
 *
 * Same conventions as TransactionsExport: every cell is a string, ';' CSV
 * with UTF-8 BOM. User-supplied text is also escaped against formula
 * injection in the CSV writer (see EscapesFormulaInjection). Stripe ids are
 * never exported.
 *
 * @implements WithMapping<PaymentHistory>
 */
class AdminPaymentsExport extends StringValueBinder implements FromQuery, WithCustomCsvSettings, WithCustomValueBinder, WithHeadings, WithMapping
{
    use EscapesFormulaInjection;

    /**
     * @param  Builder<PaymentHistory>  $payments
     * @param  string  $writerType  Maatwebsite\Excel\Excel writer constant the export is written with
     */
    public function __construct(
        private readonly Builder $payments,
        private readonly string $writerType = Excel::CSV,
    ) {}

    protected function writerType(): string
    {
        return $this->writerType;
    }

    /**
     * Newest first with the primary key as a unique tie-breaker, so chunked
     * reads never skip or repeat rows.
     *
     * @return Builder<PaymentHistory>
     */
    public function query(): Builder
    {
        return $this->payments
            ->clone()
            ->reorder()
            ->orderByRaw('COALESCE(paid_at, created_at) DESC')
            ->orderBy('payment_histories.id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data', 'Usuário', 'E-mail', 'Valor', 'Moeda', 'Situação', 'Descrição'];
    }

    /**
     * @param  PaymentHistory  $row
     * @return array<int, string>
     */
    public function map(mixed $row): array
    {
        return [
            ($row->paid_at ?? $row->created_at)?->format('d/m/Y H:i') ?? '',
            $this->text($row->user->name ?? ''),
            $this->text($row->user->email ?? ''),
            app(PlanPricingInterface::class)->format((float) $row->amount),
            $this->text($row->currency),
            $row->status_label,
            $this->text($row->description),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'line_ending' => "\n",
            'use_bom' => true,
        ];
    }
}
