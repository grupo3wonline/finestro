<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\TransactionHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoria de uma transação (quem fez o quê, quando e por quê).
 *
 * Não usa o trait BelongsToUser de propósito: o histórico é sempre acessado
 * através da transação (que já é escopada por usuário), e o registro precisa
 * ser gravável também por comandos de console (sem usuário autenticado).
 */
class TransactionHistory extends Model
{
    /** @use HasFactory<TransactionHistoryFactory> */
    use HasFactory, HasUuid;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_PAID = 'paid';

    public const ACTION_UNPAID = 'unpaid';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'action',
        'changes',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * Rótulos amigáveis dos campos auditados.
     *
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'description' => 'Descrição',
        'amount' => 'Valor',
        'type' => 'Tipo',
        'status' => 'Situação',
        'date' => 'Data',
        'due_date' => 'Vencimento',
        'wallet_id' => 'Carteira',
        'category_id' => 'Categoria',
        'payment_method' => 'Forma de pagamento',
        'notes' => 'Observações',
        'is_recurring' => 'Recorrente',
        'recurring_frequency' => 'Frequência',
        'receipt_path' => 'Comprovante',
    ];

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Criada',
            self::ACTION_UPDATED => 'Editada',
            self::ACTION_PAID => 'Marcada como paga',
            self::ACTION_UNPAID => 'Reaberta (pendente)',
            self::ACTION_DELETED => 'Excluída',
            self::ACTION_RESTORED => 'Restaurada',
            default => ucfirst($this->action),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'fa-plus text-green-500',
            self::ACTION_UPDATED => 'fa-pen text-blue-500',
            self::ACTION_PAID => 'fa-check text-green-600',
            self::ACTION_UNPAID => 'fa-rotate-left text-yellow-500',
            self::ACTION_DELETED => 'fa-trash text-red-500',
            self::ACTION_RESTORED => 'fa-trash-arrow-up text-primary-500',
            default => 'fa-circle text-gray-400',
        };
    }
}
