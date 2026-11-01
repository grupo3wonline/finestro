<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentHistories\Tables;

use App\Interfaces\Services\PlanPricingInterface;
use App\Models\PaymentHistory;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistoriesTable
{
    public const array STATUS_LABELS = [
        'paid' => 'Pago',
        'failed' => 'Falhou',
        'pending' => 'Pendente',
        'refunded' => 'Reembolsado',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->description(fn (PaymentHistory $record): string => (string) $record->user?->email)
                    ->searchable(['name', 'email'])
                    ->placeholder('Conta excluída'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (mixed $state): string => app(PlanPricingInterface::class)->format((float) $state))
                    ->sortable(),
                TextColumn::make('status_label')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (PaymentHistory $record): string => match ($record->status) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('payment_date')
                    ->label('Data')
                    ->state(fn (PaymentHistory $record): mixed => $record->paid_at ?? $record->created_at)
                    ->dateTime('d/m/Y H:i')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw('COALESCE(paid_at, created_at) '.($direction === 'asc' ? 'ASC' : 'DESC'))),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('invoice')
                    ->label('Fatura')
                    ->state(fn (PaymentHistory $record): ?string => $record->receipt_url !== null ? 'Ver fatura' : null)
                    ->url(fn (PaymentHistory $record): ?string => $record->receipt_url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->placeholder('—'),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByRaw('COALESCE(paid_at, created_at) DESC'))
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options(self::STATUS_LABELS),
                SelectFilter::make('month')
                    ->label('Mês')
                    ->options(self::monthOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $month = $data['value'] ?? null;

                        if (! is_string($month) || ! array_key_exists($month, self::monthOptions())) {
                            return $query;
                        }

                        $start = CarbonImmutable::createFromFormat('!Y-m', $month);

                        return $query->whereRaw('COALESCE(paid_at, created_at) between ? and ?', [
                            $start->startOfMonth()->toDateTimeString(),
                            $start->endOfMonth()->toDateTimeString(),
                        ]);
                    }),
            ])
            ->emptyStateHeading('Nenhum pagamento encontrado');
    }

    /**
     * The current month and the 11 before it ("Y-m" => "mm/YYYY").
     *
     * @return array<string, string>
     */
    public static function monthOptions(): array
    {
        $options = [];
        $month = CarbonImmutable::now()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $options[$month->format('Y-m')] = $month->format('m/Y');
            $month = $month->subMonth();
        }

        return $options;
    }
}
