<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\UserActions;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public const array ROLE_LABELS = ['admin' => 'Administrador', 'user' => 'Usuário'];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subscription', 'roles']))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('email_verified')
                    ->label('Verificado')
                    ->state(fn (User $record): bool => $record->hasVerifiedEmail())
                    ->boolean(),
                TextColumn::make('plan')
                    ->label('Plano')
                    ->state(fn (User $record): string => self::planLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Profissional' ? 'success' : 'gray'),
                TextColumn::make('roles.name')
                    ->label('Perfis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::ROLE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'admin' ? 'warning' : 'gray'),
                TextColumn::make('account_status')
                    ->label('Situação')
                    ->state(fn (User $record): string => $record->isSuspended() ? 'Suspensa' : 'Ativa')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Suspensa' ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar por nome, usuário ou e-mail')
            ->filters([
                SelectFilter::make('plan')
                    ->label('Plano')
                    ->options(['free' => 'Free', 'professional' => 'Profissional'])
                    ->query(self::filterByPlan(...)),
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options(self::ROLE_LABELS)
                    ->query(
                        /**
                         * @param  Builder<User>  $query
                         * @param  array<string, mixed>  $data
                         * @return Builder<User>
                         */
                        fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                            ? $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $data['value']))
                            : $query),
                TernaryFilter::make('email_verified_at')
                    ->label('E-mail verificado')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(UserActions::all()),
            ])
            ->emptyStateHeading('Nenhum usuário encontrado');
    }

    /**
     * Plan of the user's current subscription (same rule as User::current_plan).
     *
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $data
     * @return Builder<User>
     */
    private static function filterByPlan(Builder $query, array $data): Builder
    {
        return match ($data['value'] ?? null) {
            'professional' => $query->whereHas('subscription', fn (Builder $subscription) => $subscription->grantingProfessional()),
            'free' => $query->whereDoesntHave('subscription', fn (Builder $subscription) => $subscription->grantingProfessional()),
            default => $query,
        };
    }

    public static function planLabel(User $user): string
    {
        return $user->current_plan === 'professional' ? 'Profissional' : 'Free';
    }
}
