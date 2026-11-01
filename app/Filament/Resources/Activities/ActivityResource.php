<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities;

use App\Filament\Concerns\RequiresAdminAccess;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Activities\Schemas\ActivityInfolist;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use App\Interfaces\Repositories\ActivityRepositoryInterface;
use BackedEnum;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only activity log. Entries about financial records show only the
 * event, type and date (see FinancialSubjects).
 *
 * @extends FilamentResource<Activity>
 */
class ActivityResource extends FilamentResource
{
    use RequiresAdminAccess;

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Atividade';

    protected static ?string $modelLabel = 'atividade';

    protected static ?string $pluralModelLabel = 'atividades';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return app(ActivityRepositoryInterface::class)->adminQuery();
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
