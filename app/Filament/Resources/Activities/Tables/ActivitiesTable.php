<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Tables;

use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Ordering comes from the repository (newest first), so no column is
 * sortable: a column sort would only be applied after it.
 */
class ActivitiesTable
{
    public const array EVENT_LABELS = [
        'created' => 'Criou',
        'updated' => 'Alterou',
        'deleted' => 'Excluiu',
        'restored' => 'Restaurou',
        'login' => 'Entrou na conta',
        'logout' => 'Saiu da conta',
        'login_failed' => 'Tentativa de acesso sem sucesso',
        'password_reset' => 'Redefiniu a senha',
        'password_changed' => 'Alterou a senha',
        'registered' => 'Criou a conta',
        'terms_accepted' => 'Aceitou os termos',
        'consent_updated' => 'Atualizou preferências de privacidade',
        'data_export_requested' => 'Solicitou exportação de dados',
        'data_export_downloaded' => 'Baixou exportação de dados',
        'role_attached' => 'Papel atribuído',
        'role_detached' => 'Papel removido',
        'account_deleted' => 'Conta excluída',
        'api_token_created' => 'Criou um token de API',
        'api_token_revoked' => 'Revogou um token de API',
        'admin_role_assigned' => 'Admin: tornou administrador',
        'admin_role_removed' => 'Admin: removeu administrador',
        'admin_user_suspended' => 'Admin: suspendeu a conta',
        'admin_user_unsuspended' => 'Admin: reativou a conta',
        'admin_verification_resent' => 'Admin: reenviou a verificação',
        'admin_user_deleted' => 'Admin: excluiu a conta',
        'admin_mfa_reset' => 'Admin: redefiniu o MFA',
    ];

    public const array LOG_LABELS = [
        'security' => 'Segurança',
        'model' => 'Dados',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s'),
                TextColumn::make('log_name')
                    ->label('Registro')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::LOG_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => $state === 'security' ? 'warning' : 'gray'),
                TextColumn::make('event')
                    ->label('Evento')
                    ->formatStateUsing(fn (?string $state): string => self::eventLabel($state)),
                TextColumn::make('causer.name')
                    ->label('Autor')
                    ->description(fn (Activity $record): ?string => $record->causer instanceof User ? $record->causer->email : null)
                    ->placeholder('Sistema / anônimo'),
                TextColumn::make('subject_type')
                    ->label('Tipo do registro')
                    ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('causer')
                    ->label('Autor')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(AdminUserRepositoryInterface::class)->searchOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => app(AdminUserRepositoryInterface::class)->optionLabel((string) $value))
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', (string) $data['value'])
                        : $query),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options(self::EVENT_LABELS),
                Filter::make('period')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, mixed $from) => $q->whereDate('created_at', '>=', (string) $from))
                        ->when($data['until'] ?? null, fn (Builder $q, mixed $until) => $q->whereDate('created_at', '<=', (string) $until))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Nenhuma atividade registrada');
    }

    public static function eventLabel(?string $event): string
    {
        return self::EVENT_LABELS[$event] ?? (string) $event;
    }
}
