<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Concerns\RequiresAdminAccess;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Interfaces\Repositories\AdminBillingRepositoryInterface;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Resource as FilamentResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of subscriptions. Stripe ids are shown truncated only.
 *
 * @extends FilamentResource<Subscription>
 */
class SubscriptionResource extends FilamentResource
{
    use RequiresAdminAccess;

    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Assinaturas';

    protected static ?string $modelLabel = 'assinatura';

    protected static ?string $pluralModelLabel = 'assinaturas';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return app(AdminBillingRepositoryInterface::class)->subscriptionsQuery();
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }
}
