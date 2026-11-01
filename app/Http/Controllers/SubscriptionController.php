<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\SubscriptionActionException;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionServiceInterface $service,
    ) {}

    /**
     * Financial menu: current plan, validity/renewal countdown and
     * paginated payment history.
     */
    public function index(): View
    {
        $user = Auth::user();
        $subscription = $this->service->getSubscriptionForUser($user);
        $payments = $this->service->getPaymentHistory($user, 10);
        $plans = config('stripe.plans');
        $chargedPrice = $this->service->chargedPrice($subscription);

        return view('subscription.index', ['user' => $user, 'subscription' => $subscription, 'payments' => $payments, 'plans' => $plans, 'chargedPrice' => $chargedPrice]);
    }

    /**
     * Plan selection / upgrade page.
     */
    public function plans(): View
    {
        $user = Auth::user();
        $plans = config('stripe.plans');
        $currentPlan = $user->current_plan;

        return view('subscription.plans', ['user' => $user, 'plans' => $plans, 'currentPlan' => $currentPlan]);
    }

    /**
     * Create a Stripe Checkout session and redirect the user to it.
     */
    public function checkout(): RedirectResponse
    {
        try {
            $url = $this->service->startCheckout(Auth::user());
        } catch (SubscriptionActionException $e) {
            return redirect()->route('subscription.index')->with('info', $e->getMessage());
        } catch (\RuntimeException) {
            return redirect()->back()->with('error', 'Plano não configurado. Contate o suporte.');
        } catch (\Throwable $e) {
            Log::error('Stripe checkout error', ['exception' => $e::class]);

            return redirect()->back()->with('error', 'Não foi possível iniciar o pagamento. Tente novamente.');
        }

        return redirect()->away($url);
    }

    /**
     * Handle successful checkout return from Stripe. Unknown, incomplete or
     * foreign sessions answer 404.
     */
    public function success(Request $request): View|RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            abort(404);
        }

        try {
            $subscription = $this->service->handleCheckoutSuccess(Auth::user(), $sessionId);
        } catch (CheckoutSessionNotFoundException) {
            abort(404);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout success error', ['exception' => $e::class]);

            return redirect()->route('subscription.index')
                ->with('error', 'Não foi possível confirmar sua assinatura agora. Se o pagamento foi aprovado, ela será ativada em instantes.');
        }

        // Only a subscription that currently grants Pro access may see the
        // confirmation page. Every other status (past_due, unpaid, incomplete,
        // canceled, expired) goes back to Financeiro with a per-status message.
        if (! $subscription->isProfessional()) {
            return redirect()->route('subscription.index')->with('error', $subscription->ineligibleAccessMessage());
        }

        return view('subscription.success', ['subscription' => $subscription]);
    }

    /**
     * Cancel the subscription: immediately when a payment is pending,
     * otherwise scheduled for the end of the current period.
     */
    public function cancel(): RedirectResponse
    {
        try {
            $subscription = $this->service->cancel(Auth::user());
        } catch (SubscriptionActionException $e) {
            return redirect()->route('subscription.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Stripe cancel error', ['exception' => $e::class]);

            return redirect()->route('subscription.index')->with('error', 'Erro ao cancelar assinatura. Tente novamente.');
        }

        if ($subscription->status === SubscriptionStatus::CANCELED) {
            return redirect()->route('subscription.index')
                ->with('success', 'Assinatura cancelada. O Stripe não fará novas tentativas de cobrança e sua conta segue no plano Básico.');
        }

        $until = $subscription->current_period_end?->format('d/m/Y');

        return redirect()->route('subscription.index')->with('success', $until
            ? "Cancelamento agendado. Você mantém o plano Profissional até {$until}."
            : 'Cancelamento agendado. Você mantém o plano Profissional até o fim do período atual.');
    }

    /**
     * Undo a scheduled cancellation.
     */
    public function resume(): RedirectResponse
    {
        try {
            $this->service->resume(Auth::user());
        } catch (SubscriptionActionException $e) {
            return redirect()->route('subscription.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Stripe resume error', ['exception' => $e::class]);

            return redirect()->route('subscription.index')->with('error', 'Não foi possível reativar sua assinatura. Tente novamente.');
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Assinatura reativada. Sua renovação automática continua normalmente.');
    }

    /**
     * Stripe webhook endpoint (no auth, no CSRF).
     */
    public function webhook(Request $request, StripeGatewayInterface $gateway): Response
    {
        $webhookSecret = (string) config('stripe.webhook_secret');

        if ($webhookSecret === '') {
            Log::error('Stripe webhook secret not configured');

            return response('Webhook secret not configured', 400);
        }

        try {
            $event = $gateway->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $webhookSecret,
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['ip' => $request->ip()]);

            return response('Invalid signature', 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error', ['exception' => $e::class]);

            return response('Webhook error', 400);
        }

        try {
            $processed = $this->service->handleWebhookEvent($event);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'type' => $event->type,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response('Webhook processing error', 500);
        }

        return $processed
            ? response('OK', 200)
            : response('Already processed', 200);
    }
}
