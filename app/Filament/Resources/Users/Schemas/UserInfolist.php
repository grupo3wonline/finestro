<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Account, access and billing fields only. Wallets, transactions, goals,
 * budgets, categories, receipts and AI data are never shown.
 */
class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conta')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nome'),
                        TextEntry::make('username')->label('Usuário'),
                        TextEntry::make('email')->label('E-mail'),
                        TextEntry::make('email_verified_at')
                            ->label('E-mail verificado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Não verificado'),
                        TextEntry::make('created_at')->label('Cadastro')->dateTime('d/m/Y H:i'),
                        TextEntry::make('terms_accepted_at')
                            ->label('Termos aceitos em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),
                Section::make('Acesso')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Perfis')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => UsersTable::ROLE_LABELS[$state] ?? $state),
                        TextEntry::make('suspended_at')
                            ->label('Suspensa em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Conta ativa'),
                    ]),
                Section::make('Plano e assinatura')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('plan')
                            ->label('Plano')
                            ->state(fn (User $record): string => UsersTable::planLabel($record))
                            ->badge(),
                        TextEntry::make('subscription.status_label')
                            ->label('Situação da assinatura')
                            ->placeholder('Sem assinatura'),
                        TextEntry::make('subscription.current_period_start')
                            ->label('Início do período')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('subscription.current_period_end')
                            ->label('Fim do período')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('subscription.cancel_at_period_end')
                            ->label('Cancelamento agendado')
                            ->formatStateUsing(fn (mixed $state): string => $state ? 'Sim' : 'Não')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
