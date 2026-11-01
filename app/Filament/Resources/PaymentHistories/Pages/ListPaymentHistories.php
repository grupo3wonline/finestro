<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentHistories\Pages;

use App\Exports\AdminPaymentsExport;
use App\Filament\Resources\PaymentHistories\PaymentHistoryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPaymentHistories extends ListRecords
{
    protected static string $resource = PaymentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction('exportCsv', 'Exportar CSV', 'csv', ExcelWriter::CSV),
            $this->exportAction('exportXlsx', 'Exportar XLSX', 'xlsx', ExcelWriter::XLSX),
        ];
    }

    /**
     * Exports the rows matching the current table filters and search.
     */
    private function exportAction(string $name, string $label, string $extension, string $writerType): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize(fn (): bool => PaymentHistoryResource::hasAdminAccess())
            ->action(fn (ListPaymentHistories $livewire): BinaryFileResponse => Excel::download(
                new AdminPaymentsExport($livewire->getFilteredTableQuery() ?? PaymentHistoryResource::getEloquentQuery(), $writerType),
                'pagamentos-'.now()->format('Ymd-His').'.'.$extension,
                $writerType,
            ));
    }
}
