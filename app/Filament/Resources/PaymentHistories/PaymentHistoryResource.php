<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentHistories;

use App\Filament\Concerns\RequiresAdminAccess;
use App\Filament\Resources\PaymentHistories\Pages\ListPaymentHistories;
use App\Filament\Resources\PaymentHistories\Tables\PaymentHistoriesTable;
use App\Interfaces\Repositories\AdminBillingRepositoryInterface;
use App\Models\PaymentHistory;
use BackedEnum;
use Filament\Resources\Resource as FilamentResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only payment history with CSV/XLSX export. No Stripe ids.
 *
 * @extends FilamentResource<PaymentHistory>
 */
class PaymentHistoryResource extends FilamentResource
{
    use RequiresAdminAccess;

    protected static ?string $model = PaymentHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Pagamentos';

    protected static ?string $modelLabel = 'pagamento';

    protected static ?string $pluralModelLabel = 'pagamentos';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return app(AdminBillingRepositoryInterface::class)->paymentsQuery();
    }

    public static function table(Table $table): Table
    {
        return PaymentHistoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentHistories::route('/'),
        ];
    }
}
