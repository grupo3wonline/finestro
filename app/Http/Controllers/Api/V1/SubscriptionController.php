<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SubscriptionResource;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptions
    ) {}

    /**
     * Current subscription status, or the free-plan payload when the
     * account never subscribed.
     */
    #[OA\Get(
        path: '/api/v1/subscription',
        operationId: 'subscriptionShow',
        description: 'Situação da assinatura (vigência, renovação, dias restantes). Nunca expõe identificadores do Stripe. Ability: `account:read`.',
        summary: 'Assinatura atual',
        security: [['bearerAuth' => []]],
        tags: ['Conta'],
        responses: [
            new OA\Response(response: 200, description: 'Assinatura.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Subscription'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function show(Request $request): SubscriptionResource|JsonResponse
    {
        $subscription = $this->subscriptions->getSubscriptionForUser($request->user());

        if ($subscription instanceof Subscription) {
            return new SubscriptionResource($subscription);
        }

        return response()->json(['data' => [
            'plan' => 'free',
            'status' => null,
            'status_label' => 'Plano gratuito',
            'is_active' => false,
            'cancel_at_period_end' => false,
            'current_period_start' => null,
            'current_period_end' => null,
            'days_remaining' => null,
            'canceled_at' => null,
        ]]);
    }
}
