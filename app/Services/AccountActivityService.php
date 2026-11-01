<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ActivityEntryData;
use App\Interfaces\Repositories\ActivityRepositoryInterface;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Interfaces\Services\AccountActivityServiceInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class AccountActivityService implements AccountActivityServiceInterface
{
    private const array EVENT_LABELS = [
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
        'account_deleted' => 'Excluiu a conta',
        'api_token_created' => 'Criou um token de API',
        'api_token_revoked' => 'Revogou um token de API',
    ];

    private const array SUBJECT_LABELS = [
        'Wallet' => 'Carteira',
        'Transaction' => 'Transação',
        'Goal' => 'Meta',
        'Budget' => 'Orçamento',
        'Category' => 'Categoria',
        'Subscription' => 'Assinatura',
        'User' => 'Conta',
    ];

    private const array FIELD_LABELS = [
        'name' => 'Nome',
        'type' => 'Tipo',
        'ownership' => 'Titularidade',
        'company_name' => 'Empresa',
        'credit_limit' => 'Limite de crédito',
        'closing_day' => 'Dia de fechamento',
        'due_day' => 'Dia de vencimento',
        'currency' => 'Moeda',
        'color' => 'Cor',
        'icon' => 'Ícone',
        'description' => 'Descrição',
        'is_active' => 'Ativa',
        'is_shared' => 'Compartilhada',
        'wallet_id' => 'Carteira',
        'category_id' => 'Categoria',
        'status' => 'Situação',
        'amount' => 'Valor',
        'date' => 'Data',
        'due_date' => 'Vencimento',
        'paid_at' => 'Pago em',
        'payment_method' => 'Forma de pagamento',
        'is_recurring' => 'Recorrente',
        'recurring_frequency' => 'Frequência',
        'installment_number' => 'Parcela',
        'installment_total' => 'Total de parcelas',
        'target_amount' => 'Valor da meta',
        'current_amount' => 'Valor acumulado',
        'deadline' => 'Prazo',
        'year' => 'Ano',
        'month' => 'Mês',
        'alert_threshold' => 'Alerta (%)',
        'plan' => 'Plano',
        'cancel_at_period_end' => 'Cancelar no fim do período',
        'current_period_end' => 'Fim do período',
        'canceled_at' => 'Cancelada em',
        'roles' => 'Papéis',
        'token_name' => 'Token',
    ];

    /** Fields holding ids of the user's own records, shown by name. */
    private const array REFERENCE_FIELDS = ['wallet_id', 'category_id'];

    private const string DELETED_SUFFIX = '(excluída)';

    public function __construct(
        protected ActivityRepositoryInterface $activities,
        protected WalletRepositoryInterface $wallets,
        protected CategoryRepositoryInterface $categories,
    ) {}

    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $page = $this->activities->paginateForUser((string) $user->id, $perPage);

        $ids = array_fill_keys(self::REFERENCE_FIELDS, []);
        foreach ($page->items() as $activity) {
            $changes = $activity->attribute_changes?->toArray() ?? [];

            foreach (['old', 'attributes'] as $side) {
                foreach (self::REFERENCE_FIELDS as $field) {
                    $value = $changes[$side][$field] ?? null;

                    if (is_string($value) && $value !== '') {
                        $ids[$field][$value] = $value;
                    }
                }
            }
        }

        // Two queries per page; only the user's own records are resolved, so a
        // foreign id never reveals another user's name.
        $names = [
            'wallet_id' => $this->wallets->namesForUser((string) $user->id, array_values($ids['wallet_id'])),
            'category_id' => $this->categories->namesForUser((string) $user->id, array_values($ids['category_id'])),
        ];

        return $page->through(fn (Activity $activity): ActivityEntryData => $this->toEntry($activity, $names));
    }

    /**
     * @param  array<string, array<string, array{name: string, deleted: bool}>>  $names
     */
    private function toEntry(Activity $activity, array $names): ActivityEntryData
    {
        $event = (string) ($activity->event ?? $activity->description);
        $subjectType = $activity->subject_type !== null ? class_basename($activity->subject_type) : 'User';
        $ip = $activity->getProperty('ip');

        return new ActivityEntryData(
            date: $activity->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
            event: $event,
            eventLabel: self::EVENT_LABELS[$event] ?? $event,
            subjectLabel: self::SUBJECT_LABELS[$subjectType] ?? $subjectType,
            changes: $this->changes($activity, $names),
            ip: is_string($ip) ? $ip : null,
            viaSystem: $activity->getProperty('via') === 'system',
        );
    }

    /**
     * @param  array<string, array<string, array{name: string, deleted: bool}>>  $names
     * @return list<array{field: string, old: string|null, new: string|null}>
     */
    private function changes(Activity $activity, array $names): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $new = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];

        $roles = $activity->getProperty('roles');
        if (is_array($roles) && $roles !== []) {
            $new['roles'] = implode(', ', $roles);
        }

        $tokenName = $activity->getProperty('token_name');
        if (is_string($tokenName) && $tokenName !== '') {
            $new['token_name'] = $tokenName;
        }

        $rows = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $map = $names[$field] ?? null;

            $rows[] = [
                'field' => self::FIELD_LABELS[$field] ?? (string) $field,
                'old' => $map === null ? $this->format($old[$field] ?? null) : $this->formatReference($old[$field] ?? null, $map),
                'new' => $map === null ? $this->format($new[$field] ?? null) : $this->formatReference($new[$field] ?? null, $map),
            ];
        }

        return $rows;
    }

    /**
     * "Nome", "Nome (excluída)" or "(excluída)" when the id is not one of the
     * user's records (never the raw id).
     *
     * @param  array<string, array{name: string, deleted: bool}>  $map
     */
    private function formatReference(mixed $value, array $map): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $entry = is_string($value) ? ($map[$value] ?? null) : null;

        if ($entry === null) {
            return self::DELETED_SUFFIX;
        }

        return $entry['deleted'] ? $entry['name'].' '.self::DELETED_SUFFIX : $entry['name'];
    }

    private function format(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'Sim' : 'Não',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        };
    }
}
