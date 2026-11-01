<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schemas shared by every /api/v1 operation: the error envelope and the
 * Laravel paginator `links`/`meta` blocks. Kept apart from OpenApiSpec because
 * swagger-php nests class-level Schema attributes into sibling Parameter ones.
 */
#[OA\Schema(
    schema: 'ErrorEnvelope',
    description: 'Envelope de erro de todas as respostas /api/*.',
    required: ['message', 'code'],
    properties: [
        new OA\Property(property: 'message', description: 'Mensagem em pt-BR para exibir ao usuário.', type: 'string', example: 'Recurso não encontrado.'),
        new OA\Property(
            property: 'code',
            type: 'string',
            example: 'not_found',
            enum: [
                'unauthenticated', 'missing_ability', 'plan_upgrade_required', 'plan_limit_reached', 'forbidden',
                'not_found', 'validation_failed', 'too_many_requests', 'http_error', 'server_error',
            ],
        ),
        new OA\Property(
            property: 'errors',
            description: 'Somente em 422: campo → lista de mensagens.',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
        new OA\Property(
            property: 'abilities',
            description: 'Somente em missing_ability: abilities que faltam no token.',
            type: 'array',
            items: new OA\Items(type: 'string'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    properties: [
        new OA\Property(property: 'first', type: 'string', example: 'http://finestro.test/api/v1/wallets?page=1'),
        new OA\Property(property: 'last', type: 'string', example: 'http://finestro.test/api/v1/wallets?page=3'),
        new OA\Property(property: 'prev', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'next', type: 'string', example: 'http://finestro.test/api/v1/wallets?page=2', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'links', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'path', type: 'string', example: 'http://finestro.test/api/v1/wallets'),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'to', type: 'integer', example: 20, nullable: true),
        new OA\Property(property: 'total', type: 'integer', example: 45),
    ],
    type: 'object',
)]
final class CommonSchemas {}
