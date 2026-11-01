<?php

namespace App\Repositories;

use App\Interfaces\Repositories\PaymentHistoryRepositoryInterface;
use App\Models\PaymentHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentHistoryRepository implements PaymentHistoryRepositoryInterface
{
    public function __construct(
        protected PaymentHistory $model
    ) {}

    public function getRecentForUser(string $userId, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function paginateForUser(string $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByRaw('COALESCE(paid_at, created_at) DESC')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function latestPaidForSubscription(string $userId, string $subscriptionId): ?PaymentHistory
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('subscription_id', $subscriptionId)
            ->where('status', 'paid')
            ->orderByRaw('COALESCE(paid_at, created_at) DESC')
            ->first();
    }

    public function record(array $data): PaymentHistory
    {
        return $this->model->newQuery()->create($data);
    }

    public function upsertForInvoice(string $userId, string $invoiceId, array $data): PaymentHistory
    {
        $keys = ['user_id' => $userId, 'stripe_invoice_id' => $invoiceId];

        $existing = $this->model->newQuery()->where($keys)->first();

        if ($existing && $existing->status === 'paid' && ($data['status'] ?? null) === 'failed') {
            return $existing;
        }

        return $this->model->newQuery()->updateOrCreate($keys, $data);
    }
}
