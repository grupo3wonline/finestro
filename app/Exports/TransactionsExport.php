<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\EscapesFormulaInjection;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Transaction statement export (single source of columns for CSV, XLSX and
 * the e-mail attachment).
 *
 * Every cell is bound as a string (StringValueBinder), so amounts keep the
 * pt-BR "1.234,56" format. StringValueBinder only types XLSX cells, so
 * user-supplied text is additionally escaped against formula injection in
 * the CSV writer (see EscapesFormulaInjection).
 *
 * @implements FromCollection<int, Transaction>
 * @implements WithMapping<Transaction>
 */
class TransactionsExport extends StringValueBinder implements FromCollection, WithCustomCsvSettings, WithCustomValueBinder, WithHeadings, WithMapping
{
    use EscapesFormulaInjection;

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  string  $writerType  Maatwebsite\Excel\Excel writer constant the export is written with
     */
    public function __construct(
        private readonly Collection $transactions,
        private readonly string $writerType = Excel::CSV,
    ) {}

    protected function writerType(): string
    {
        return $this->writerType;
    }

    /**
     * @return Enumerable<int, Transaction>
     */
    public function collection(): Enumerable
    {
        return $this->transactions;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data', 'Vencimento', 'Descrição', 'Parcela', 'Tipo', 'Situação', 'Forma de pagamento', 'Carteira', 'Categoria', 'Valor'];
    }

    /**
     * @param  Transaction  $row
     * @return array<int, string>
     */
    public function map(mixed $row): array
    {
        return [
            $row->date->format('d/m/Y'),
            $row->due_date?->format('d/m/Y') ?? '',
            $this->text($row->description),
            $this->text($row->installment_label),
            $row->type === 'income' ? 'Receita' : 'Despesa',
            (string) $row->status_label,
            $row->payment_method_label ?? '',
            $this->text($row->wallet->name ?? '-'),
            $this->text($row->category->name ?? '-'),
            number_format((float) $row->amount, 2, ',', '.'),
        ];
    }

    /**
     * CSV layout kept from the original hand-written export: ';' separator
     * and UTF-8 BOM so Excel (pt-BR) opens accents and columns correctly.
     *
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
