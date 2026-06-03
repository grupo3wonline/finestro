<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Webhook;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    /**
     * Show subscription management page (financial menu).
     */
    public function index(): View
    {
        $user = Auth::user();
        $subscription = $user->subscription;
        $payments = PaymentHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $plans = config('stripe.plans');

        return view('subscription.index', compact('user', 'subscription', 'payments', 'plans'));
    }

    /**
     * Show plan selection/upgrade page.
     */
    public function plans(): View
    {
        $user = Auth::user();
        $plans = config('stripe.plans');
        $currentPlan = $user->current_plan;

        return view('subscription.plans', compact('user', 'plans', 'currentPlan'));
    }

    /**
     * Create Stripe Checkout session for subscription.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $priceId = config('stripe.plans.professional.stripe_price_id');

        if (!$priceId) {
            return redirect()->back()->with('error', 'Plano não configurado. Contate o suporte.');
        }

        // Get or create Stripe customer
        $subscription = $user->subscription;
        $customerId = $subscription?->stripe_customer_id;

        if (!$customerId) {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => $user->id],
            ]);
            $customerId = $customer->id;
        }

        $session = StripeSession::create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscription.plans'),
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return redirect($session->url);
    }

    /**
     * Handle successful checkout return.
     */
    public function success(Request $request): View
    {
        $sessionId = $request->get('session_id');

        if ($sessionId) {
            try {
                $session = StripeSession::retrieve([
                    'id' => $sessionId,
                    'expand' => ['subscription'],
                ]);

                $user = Auth::user();
                $stripeSubscription = $session->subscription;

                // If subscription is a string ID (not expanded), fetch it directly
                if (is_string($stripeSubscription)) {
                    $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscription);
                }

                // Safely get period timestamps with fallbacks
                $periodStart = $stripeSubscription->current_period_start
                    ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)
                    : now();
                $periodEnd = $stripeSubscription->current_period_end
                    ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
                    : now()->addMonth();

                // Create or update local subscription
                Subscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'stripe_subscription_id' => $stripeSubscription->id,
                        'stripe_customer_id' => $session->customer,
                        'stripe_price_id' => config('stripe.plans.professional.stripe_price_id'),
                        'plan' => 'professional',
                        'status' => 'active',
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                    ]
                );

                // Record payment
                PaymentHistory::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->fresh()->subscription->id,
                    'stripe_payment_intent_id' => $session->payment_intent ?? null,
                    'amount' => config('stripe.plans.professional.price', 29.90),
                    'currency' => 'BRL',
                    'status' => 'paid',
                    'description' => 'Assinatura Plano Profissional',
                    'paid_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Stripe checkout success error', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('subscription.success');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(): RedirectResponse
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if ($subscription && $subscription->stripe_subscription_id) {
            try {
                $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
                $stripeSubscription->cancel();

                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Erro ao cancelar assinatura. Tente novamente.');
            }
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Assinatura cancelada. Você terá acesso até o fim do período atual.');
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (!$webhookSecret) {
            \Log::error('Stripe webhook secret not configured');
            return response('Webhook secret not configured', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            \Log::warning('Stripe webhook signature verification failed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            \Log::error('Stripe webhook error', ['error' => $e->getMessage()]);
            return response('Webhook error', 400);
        }

        \Log::info('Stripe webhook received', ['type' => $event->type, 'id' => $event->id]);

        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
        }

        return response('OK', 200);
    }

    private function handlePaymentSucceeded($invoice): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if ($subscription) {
            PaymentHistory::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'stripe_invoice_id' => $invoice->id,
                'stripe_payment_intent_id' => $invoice->payment_intent,
                'amount' => $invoice->amount_paid / 100,
                'currency' => strtoupper($invoice->currency),
                'status' => 'paid',
                'description' => 'Renovação Plano Profissional',
                'paid_at' => now(),
            ]);
        }
    }

    private function handlePaymentFailed($invoice): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);

            PaymentHistory::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'stripe_invoice_id' => $invoice->id,
                'amount' => $invoice->amount_due / 100,
                'currency' => strtoupper($invoice->currency),
                'status' => 'failed',
                'description' => 'Falha no pagamento',
                'paid_at' => null,
            ]);
        }
    }

    private function handleSubscriptionUpdated($stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update([
                'status' => $stripeSubscription->status === 'active' ? 'active' : $stripeSubscription->status,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
            ]);
        }
    }

    private function handleSubscriptionDeleted($stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);
        }
    }
}
