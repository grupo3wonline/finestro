<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Support\StripeId;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->description(fn (Subscription $record): string => (string) $record->user?->email)
                    ->searchable(['name', 'email'])
                    ->placeholder('Conta excluída'),
                TextColumn::make('plan')
                    ->label('Plano')
                    ->formatStateUsing(fn (?string $state): string => $state === 'professional' ? 'Profissional' : 'Free')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'professional' ? 'success' : 'gray'),
                TextColumn::make('status_label')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (Subscription $record): string => match (true) {
                        $record->isProfessional() => 'success',
                        $record->isPaymentPending() => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('period')
                    ->label('Período')
                    ->state(fn (Subscription $record): string => self::period($record)),
                TextColumn::make('cancel_at_period_end')
                    ->label('Cancelamento agendado')
                    ->formatStateUsing(fn (mixed $state): string => $state ? 'Sim' : 'Não'),
                TextColumn::make('stripe_subscription_id')
                    ->label('ID no Stripe')
                    ->formatStateUsing(fn (?string $state): string => StripeId::short($state))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options(collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->emptyStateHeading('Nenhuma assinatura encontrada');
    }

    private static function period(Subscription $subscription): string
    {
        $start = $subscription->current_period_start?->format('d/m/Y');
        $end = $subscription->current_period_end?->format('d/m/Y');

        if ($start === null && $end === null) {
            return '—';
        }

        return ($start ?? '—').' – '.($end ?? '—');
    }
}
