<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'transactions:process-recurring';
    protected $description = 'Process recurring transactions and create new entries';

    public function handle(): int
    {
        $today = Carbon::today();

        $recurringTransactions = Transaction::where('is_recurring', true)
            ->whereNotNull('recurring_frequency')
            ->get();

        $created = 0;

        foreach ($recurringTransactions as $transaction) {
            $lastDate = $transaction->date;
            $nextDate = $this->getNextDate($lastDate, $transaction->recurring_frequency);

            if ($nextDate->lte($today)) {
                // Check if already created for this period
                $exists = Transaction::where('user_id', $transaction->user_id)
                    ->where('description', $transaction->description)
                    ->where('wallet_id', $transaction->wallet_id)
                    ->where('amount', $transaction->amount)
                    ->where('date', $nextDate->toDateString())
                    ->exists();

                if (!$exists) {
                    Transaction::create([
                        'user_id' => $transaction->user_id,
                        'wallet_id' => $transaction->wallet_id,
                        'category_id' => $transaction->category_id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'date' => $nextDate,
                        'is_recurring' => true,
                        'recurring_frequency' => $transaction->recurring_frequency,
                        'notes' => $transaction->notes,
                    ]);

                    // Update wallet balance
                    $wallet = $transaction->wallet;
                    if ($wallet) {
                        $newBalance = $transaction->type === 'income'
                            ? $wallet->balance + $transaction->amount
                            : $wallet->balance - $transaction->amount;
                        $wallet->update(['balance' => $newBalance]);
                    }

                    $created++;
                }
            }
        }

        $this->info("Processed {$created} recurring transaction(s).");

        return Command::SUCCESS;
    }

    private function getNextDate(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonth(),
            'yearly' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };
    }
}
