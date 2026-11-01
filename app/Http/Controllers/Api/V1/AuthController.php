<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * The account that owns the token, its plan and the token's abilities.
     */
    #[OA\Get(
        path: '/api/v1/auth/me',
        operationId: 'authMe',
        description: 'Dados do usuário dono do token, recursos e limites do plano e o token em uso. Ability: `account:read`.',
        summary: 'Usuário autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Conta'],
        responses: [
            new OA\Response(response: 200, description: 'Usuário autenticado.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/User'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
