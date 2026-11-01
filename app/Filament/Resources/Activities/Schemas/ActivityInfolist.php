<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use App\Models\User;
use App\Support\Admin\FinancialSubjects;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * For financial subjects only the event, type and date are shown; the
 * stored attribute changes and properties are never rendered.
 */
class ActivityInfolist
{
    public const string HIDDEN_NOTICE = 'Detalhes ocultos (dados financeiros do usuário)';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evento')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('event')
                            ->label('Evento')
                            ->formatStateUsing(fn (?string $state): string => ActivitiesTable::eventLabel($state)),
                        TextEntry::make('log_name')
                            ->label('Registro')
                            ->formatStateUsing(fn (?string $state): string => ActivitiesTable::LOG_LABELS[$state] ?? (string) $state),
                        TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('subject_type')
                            ->label('Tipo do registro')
                            ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                            ->placeholder('—'),
                        TextEntry::make('causer_label')
                            ->label('Autor')
                            ->state(fn (Activity $record): ?string => $record->causer instanceof User
                                ? "{$record->causer->name} ({$record->causer->email})"
                                : null)
                            ->placeholder('Sistema / anônimo'),
                    ]),
                Section::make('Detalhes')
                    ->schema([
                        TextEntry::make('hidden_notice')
                            ->hiddenLabel()
                            ->state(self::HIDDEN_NOTICE)
                            ->visible(fn (Activity $record): bool => self::isFinancial($record)),
                        KeyValueEntry::make('changes')
                            ->label('Alterações registradas')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->state(fn (Activity $record): array => self::flatten($record->attribute_changes))
                            ->visible(fn (Activity $record): bool => ! self::isFinancial($record)),
                        KeyValueEntry::make('details')
                            ->label('Propriedades')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->state(fn (Activity $record): array => self::flatten($record->properties))
                            ->visible(fn (Activity $record): bool => ! self::isFinancial($record)),
                    ]),
            ]);
    }

    private static function isFinancial(Activity $activity): bool
    {
        return FinancialSubjects::isFinancial($activity->subject_type);
    }

    /**
     * Nested stored values as "a.b" => "value" strings.
     *
     * @param  Collection<array-key, mixed>|array<array-key, mixed>|null  $values
     * @return array<string, string>
     */
    private static function flatten(Collection|array|null $values): array
    {
        $values = $values instanceof Collection ? $values->all() : ($values ?? []);
        $flat = [];

        foreach (Arr::dot($values) as $key => $value) {
            $flat[(string) $key] = match (true) {
                $value === null => '—',
                is_bool($value) => $value ? 'Sim' : 'Não',
                is_scalar($value) => (string) $value,
                default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            };
        }

        return $flat;
    }
}
