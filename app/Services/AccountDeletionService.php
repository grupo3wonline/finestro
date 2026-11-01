<?php

namespace App\Services;

use App\Exceptions\AccountDeletionException;
use App\Exceptions\StripeResourceMissingException;
use App\Interfaces\Services\AccountDeletionServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AccountDeletionService implements AccountDeletionServiceInterface
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptions,
        protected SecurityActivityLoggerInterface $security,
    ) {}

    public function delete(User $user, ?User $actor = null): void
    {
        $subscription = $this->subscriptions->getSubscriptionForUser($user);

        // Cancel in Stripe first, so a deleted account is never charged again.
        if ($subscription?->isOpenInStripe()) {
            try {
                $this->subscriptions->cancelImmediately($subscription);
            } catch (StripeResourceMissingException $e) {
                // Nothing left to cancel in Stripe: end access locally and continue.
                Log::error('Account deletion: Stripe subscription already gone, marking canceled locally', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'exception' => $e::class,
                ]);

                $this->subscriptions->markCanceledLocally($subscription);
            } catch (\Throwable $e) {
                Log::error('Account deletion blocked: Stripe cancellation failed', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'exception' => $e::class,
                ]);

                throw new AccountDeletionException(
                    'Não foi possível cancelar sua assinatura no Stripe, por isso sua conta não foi excluída. Tente novamente em instantes ou fale com o suporte.',
                    0,
                    $e,
                );
            }
        }

        $this->security->log('account_deleted', $user, [], $actor);

        // Revoke every API token so a deleted account can no longer call the API.
        $user->tokens()->delete();

        $user->delete();
    }
}
