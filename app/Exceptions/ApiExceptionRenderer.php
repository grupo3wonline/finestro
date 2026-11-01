<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every error under api/* as {"message", "code"[, "errors"]} with a
 * pt-BR message. Never echoes model classes, ids or stack traces.
 */
class ApiExceptionRenderer
{
    private const string FORBIDDEN = 'Você não tem permissão para realizar esta ação.';

    private const string NOT_FOUND = 'Recurso não encontrado.';

    /** Framework default messages that must not reach API clients. */
    private const array DEFAULT_AUTHORIZATION_MESSAGES = ['', 'This action is unauthorized.'];

    /** @var array<int, string> */
    private const array HTTP_MESSAGES = [
        400 => 'Requisição inválida.',
        403 => 'Acesso negado.',
        405 => 'Método não permitido.',
        406 => 'Formato de resposta não suportado.',
        413 => 'O conteúdo enviado é grande demais.',
        415 => 'Tipo de conteúdo não suportado.',
        419 => 'Sessão expirada.',
        503 => 'Serviço temporariamente indisponível.',
    ];

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        // A ready-made response (e.g. from a Form Request) is kept as is.
        if ($e instanceof HttpResponseException) {
            return null;
        }

        $e = $this->unwrap($e);

        return match (true) {
            $e instanceof AuthenticationException => $this->error(401, 'unauthenticated',
                'Não autenticado. Envie um token válido no cabeçalho Authorization: Bearer.'),
            $e instanceof MissingAbilityException => $this->error(403, 'missing_ability',
                'Este token não tem a permissão necessária para esta ação.',
                ['abilities' => array_values($e->abilities())]),
            $e instanceof PlanLimitException => $this->error(403, $e->errorCode(), $e->getMessage()),
            $e instanceof AuthorizationException => $e->hasStatus() && $e->status() === 404
                ? $this->error(404, 'not_found', self::NOT_FOUND)
                : $this->error(403, 'forbidden', $this->authorizationMessage($e)),
            $e instanceof UnauthorizedException => $this->error(403, 'forbidden', self::FORBIDDEN),
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => $this->error(404, 'not_found', self::NOT_FOUND),
            $e instanceof ValidationException => $this->error(422, 'validation_failed',
                'Os dados enviados são inválidos.', ['errors' => $e->errors()]),
            $e instanceof ThrottleRequestsException => $this->error(429, 'too_many_requests',
                'Muitas requisições. Aguarde e tente novamente.', [], $e->getHeaders()),
            $e instanceof HttpExceptionInterface => $this->error($e->getStatusCode(), 'http_error',
                $this->httpMessage($e), [], $e->getHeaders()),
            default => $this->serverError(),
        };
    }

    /**
     * The handler converts authorization/model errors into HTTP exceptions
     * before render callbacks run; the original carries the details.
     */
    private function unwrap(Throwable $e): Throwable
    {
        $previous = $e->getPrevious();

        if ($e instanceof HttpExceptionInterface
            && ($previous instanceof AuthorizationException || $previous instanceof ModelNotFoundException)) {
            return $previous;
        }

        return $e;
    }

    private function authorizationMessage(AuthorizationException $e): string
    {
        $message = $e->getMessage();

        return in_array($message, self::DEFAULT_AUTHORIZATION_MESSAGES, true) ? self::FORBIDDEN : $message;
    }

    private function httpMessage(HttpExceptionInterface $e): string
    {
        $status = $e->getStatusCode();

        // EnsureEmailIsVerified aborts with this English message for JSON requests.
        if ($status === 403 && str_contains($e->getMessage(), 'email address is not verified')) {
            return 'Confirme seu e-mail para usar a API.';
        }

        return self::HTTP_MESSAGES[$status] ?? ($status >= 500
            ? 'Erro interno. Tente novamente mais tarde.'
            : 'Não foi possível processar a requisição.');
    }

    /**
     * Local development keeps Laravel's debug output; everywhere else the
     * client only gets a generic message (the error is still reported).
     */
    private function serverError(): ?JsonResponse
    {
        if (app()->isLocal() && config('app.debug')) {
            return null;
        }

        return $this->error(500, 'server_error', 'Erro interno. Tente novamente mais tarde.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $headers
     */
    private function error(int $status, string $code, string $message, array $extra = [], array $headers = []): JsonResponse
    {
        return new JsonResponse(['message' => $message, 'code' => $code] + $extra, $status, $headers);
    }
}
