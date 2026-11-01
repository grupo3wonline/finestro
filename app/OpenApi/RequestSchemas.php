<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Request body schemas of the /api/v1 write operations. They mirror the
 * Form Requests (StoreWalletRequest, StoreTransactionRequest, ...), which
 * remain the source of truth for validation.
 */
#[OA\Schema(
    schema: 'WalletInput',
    required: ['name', 'type', 'ownership'],
    properties: [
        new OA\Property(property: 'name', description: 'Nome da carteira.', type: 'string', example: 'Conta Corrente', maxLength: 255),
        new OA\Property(property: 'type', type: 'string', example: 'checking', enum: ['checking', 'savings', 'credit', 'investment', 'cash']),
        new OA\Property(property: 'ownership', type: 'string', example: 'personal', enum: ['personal', 'business']),
        new OA\Property(property: 'company_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'company_document', type: 'string', nullable: true, maxLength: 20),
        new OA\Property(property: 'balance', type: 'number', example: 1500.5, minimum: 0),
        new OA\Property(property: 'credit_limit', type: 'number', nullable: true, minimum: 0),
        new OA\Property(property: 'closing_day', type: 'integer', nullable: true, maximum: 31, minimum: 1),
        new OA\Property(property: 'due_day', type: 'integer', nullable: true, maximum: 31, minimum: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL', maxLength: 3, minLength: 3),
        new OA\Property(property: 'color', type: 'string', example: '#10b981', maxLength: 7),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-building-columns', maxLength: 50),
        new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
        new OA\Property(property: 'is_shared', type: 'boolean', example: false),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'WalletUpdateInput',
    description: 'Corpo parcial (PUT e PATCH): envie apenas os campos que deseja alterar. Campos omitidos mantêm o valor atual. Enviar null em company_name, company_document, credit_limit, closing_day ou due_day limpa o campo; nos demais campos, null mantém o valor atual. Espelha UpdateWalletRequest.',
    properties: [
        new OA\Property(property: 'name', description: 'Nome da carteira.', type: 'string', example: 'Conta Corrente', maxLength: 255),
        new OA\Property(property: 'type', type: 'string', example: 'checking', enum: ['checking', 'savings', 'credit', 'investment', 'cash']),
        new OA\Property(property: 'ownership', type: 'string', example: 'personal', enum: ['personal', 'business']),
        new OA\Property(property: 'company_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'company_document', type: 'string', nullable: true, maxLength: 20),
        new OA\Property(property: 'balance', type: 'number', example: 1500.5, nullable: true),
        new OA\Property(property: 'credit_limit', type: 'number', nullable: true, minimum: 0),
        new OA\Property(property: 'closing_day', type: 'integer', nullable: true, maximum: 31, minimum: 1),
        new OA\Property(property: 'due_day', type: 'integer', nullable: true, maximum: 31, minimum: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL', nullable: true, maxLength: 3, minLength: 3),
        new OA\Property(property: 'color', type: 'string', example: '#10b981', nullable: true, maxLength: 7),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-building-columns', nullable: true, maxLength: 50),
        new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TransactionInput',
    required: ['wallet_id', 'type', 'amount', 'description', 'date'],
    properties: [
        new OA\Property(property: 'wallet_id', description: 'Carteira do próprio usuário.', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category_id', description: 'Categoria do próprio usuário.', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'type', type: 'string', example: 'expense', enum: ['income', 'expense']),
        new OA\Property(property: 'amount', description: 'Aceita 1234.56 ou 1.234,56.', type: 'number', example: 89.9, minimum: 0.01),
        new OA\Property(property: 'description', type: 'string', example: 'Mercado Central', maxLength: 255),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-05-10'),
        new OA\Property(property: 'status', type: 'string', example: 'paid', enum: ['paid', 'pending']),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'payment_method', type: 'string', example: 'pix', enum: ['pix', 'cash', 'debit_card', 'credit_card', 'boleto', 'transfer', 'auto_debit', 'other']),
        new OA\Property(property: 'is_recurring', type: 'boolean', example: false),
        new OA\Property(property: 'recurring_frequency', description: 'Obrigatório quando is_recurring=true.', type: 'string', nullable: true, enum: ['daily', 'weekly', 'monthly', 'yearly']),
        new OA\Property(property: 'installments', description: 'Somente na criação: 1 a 120 parcelas.', type: 'integer', example: 1, minimum: 1),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 1000),
        new OA\Property(property: 'remove_receipt', description: 'Somente na atualização: remove o comprovante.', type: 'boolean'),
        new OA\Property(property: 'reason', description: 'Justificativa registrada no histórico.', type: 'string', nullable: true, maxLength: 255),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TransactionMultipartInput',
    description: 'Mesmos campos de TransactionInput, com o arquivo do comprovante.',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/TransactionInput'),
        new OA\Schema(properties: [
            new OA\Property(property: 'receipt', description: 'JPG, PNG ou PDF até 5 MB.', type: 'string', format: 'binary'),
        ], type: 'object'),
    ],
)]
#[OA\Schema(
    schema: 'GoalInput',
    required: ['name', 'target_amount'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Reserva de emergência', maxLength: 255),
        new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500),
        new OA\Property(property: 'target_amount', type: 'number', example: 10000, minimum: 0.01),
        new OA\Property(property: 'current_amount', type: 'number', example: 2500, minimum: 0),
        new OA\Property(property: 'deadline', description: 'Data futura.', type: 'string', format: 'date', example: '2027-12-31', nullable: true),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-piggy-bank', maxLength: 50),
        new OA\Property(property: 'color', type: 'string', example: '#6366f1', maxLength: 7),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'CategoryInput',
    required: ['name', 'type'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Alimentação', maxLength: 100),
        new OA\Property(property: 'type', type: 'string', example: 'expense', enum: ['income', 'expense']),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-utensils', maxLength: 50),
        new OA\Property(property: 'color', type: 'string', example: '#f59e0b', maxLength: 7),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'BudgetInput',
    required: ['year', 'month', 'amount'],
    properties: [
        new OA\Property(property: 'category_id', description: 'Categoria de despesa do usuário; omita para um orçamento geral do mês.', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'year', type: 'integer', example: 2026, maximum: 2100, minimum: 2000),
        new OA\Property(property: 'month', type: 'integer', example: 5, maximum: 12, minimum: 1),
        new OA\Property(property: 'amount', type: 'number', example: 800, minimum: 1),
        new OA\Property(property: 'alert_threshold', description: 'Percentual para alerta (10 a 100).', type: 'integer', example: 80, maximum: 100, minimum: 10),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'repeat_until_december', description: 'Repete o orçamento até dezembro.', type: 'boolean', example: false),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'BudgetUpdateInput',
    description: 'category_id, year e month não podem ser alterados (422 se enviados).',
    required: ['amount'],
    properties: [
        new OA\Property(property: 'amount', type: 'number', example: 950, minimum: 1),
        new OA\Property(property: 'alert_threshold', type: 'integer', example: 90, maximum: 100, minimum: 10),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 255),
    ],
    type: 'object',
)]
final class RequestSchemas {}
