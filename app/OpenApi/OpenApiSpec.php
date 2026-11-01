<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root of the OpenAPI document (read by `php artisan l5-swagger:generate`):
 * info, server, bearer security scheme, tags and the components shared by
 * every /api/v1 operation (error envelope, error responses, pagination).
 * Operations live on the controllers in app/Http/Controllers/Api/V1 and the
 * resource schemas on app/Http/Resources/V1.
 */
#[OA\Info(version: '1.0.0', description: 'API REST v1 do Finestro (somente plano Profissional). Autentique com um token pessoal criado em '
    .'Configurações → Tokens de API, enviado no cabeçalho `Authorization: Bearer <token>`. Cada token só acessa '
    .'os dados do próprio usuário e apenas as ações das permissões (abilities) marcadas na criação. '
    .'Limite: 60 requisições por minuto por usuário (padrão). Todas as respostas de erro usam o envelope '
    .'`{message, code, errors?}` com mensagens em pt-BR.', title: 'Finestro API')]
#[OA\Server(url: '/', description: 'Finestro')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Token pessoal do Laravel Sanctum no formato `{id}|{segredo}`. Crie em Configurações → Tokens de API.',
    bearerFormat: 'Sanctum token',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Conta', description: 'Usuário autenticado, plano e assinatura.')]
#[OA\Tag(name: 'Carteiras', description: 'Contas financeiras do usuário.')]
#[OA\Tag(name: 'Transações', description: 'Receitas e despesas, pagamento e comprovantes.')]
#[OA\Tag(name: 'Metas', description: 'Metas financeiras com progresso.')]
#[OA\Tag(name: 'Orçamentos', description: 'Orçamentos mensais por categoria ou gerais.')]
#[OA\Tag(name: 'Categorias', description: 'Categorias de receita e despesa.')]
#[OA\Tag(name: 'Relatórios', description: 'Resumo financeiro por período.')]
#[OA\Response(
    response: 'Unauthorized',
    description: 'Token ausente, inválido, revogado ou expirado.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorEnvelope',
        example: [
            'message' => 'Não autenticado. Envie um token válido no cabeçalho Authorization: Bearer.',
            'code' => 'unauthenticated',
        ],
    ),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'Token sem a ability exigida, plano sem acesso à API, e-mail não confirmado ou papel sem a permissão.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorEnvelope',
        examples: [
            new OA\Examples(
                example: 'missing_ability',
                summary: 'Token sem a ability',
                value: [
                    'message' => 'Este token não tem a permissão necessária para esta ação.',
                    'code' => 'missing_ability',
                    'abilities' => ['wallets:write'],
                ],
            ),
            new OA\Examples(
                example: 'plan_upgrade_required',
                summary: 'Plano gratuito',
                value: [
                    'message' => 'O acesso à API está disponível no plano Profissional. Faça upgrade para usar seus tokens.',
                    'code' => 'plan_upgrade_required',
                ],
            ),
            new OA\Examples(
                example: 'forbidden',
                summary: 'Papel sem a permissão',
                value: ['message' => 'Você não tem permissão para realizar esta ação.', 'code' => 'forbidden'],
            ),
        ],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Registro inexistente ou de outro usuário (nunca revela qual).',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorEnvelope',
        example: ['message' => 'Recurso não encontrado.', 'code' => 'not_found'],
    ),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Dados inválidos.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorEnvelope',
        example: [
            'message' => 'Os dados enviados são inválidos.',
            'code' => 'validation_failed',
            'errors' => ['name' => ['O nome da carteira é obrigatório.']],
        ],
    ),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Limite de requisições por minuto atingido (cabeçalhos Retry-After e X-RateLimit-*).',
    headers: [
        new OA\Header(header: 'Retry-After', description: 'Segundos até liberar.', schema: new OA\Schema(type: 'integer')),
    ],
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorEnvelope',
        example: ['message' => 'Muitas requisições. Aguarde e tente novamente.', 'code' => 'too_many_requests'],
    ),
)]
#[OA\Parameter(
    parameter: 'PerPage',
    name: 'per_page',
    description: 'Itens por página (1 a 100).',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1),
)]
#[OA\Parameter(
    parameter: 'Page',
    name: 'page',
    description: 'Número da página.',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
)]
final class OpenApiSpec {}
