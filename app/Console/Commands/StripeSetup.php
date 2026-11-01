<?php

namespace App\Console\Commands;

use App\Interfaces\Services\PlanPricingInterface;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeSetup extends Command
{
    protected $signature = 'stripe:setup';

    protected $description = 'Create Stripe product and price for the Professional plan';

    public function handle(PlanPricingInterface $pricing): int
    {
        $secretKey = config('stripe.secret_key');

        if (! $secretKey) {
            $this->error('STRIPE_SECRET_KEY not configured in .env');

            return Command::FAILURE;
        }

        // Derive the price from config so Stripe always matches the UI.
        $priceReais = $pricing->regularPrice();

        if ($priceReais <= 0) {
            $this->error('STRIPE_PROFESSIONAL_PRICE must be greater than zero.');

            return Command::FAILURE;
        }

        $currency = strtolower((string) config('stripe.currency', 'BRL'));

        Stripe::setApiKey($secretKey);

        $this->info('Creating Stripe product...');

        $product = Product::create([
            'name' => 'Finestro Profissional',
            'description' => 'Plano Profissional do Finestro - Gestão financeira completa com recursos ilimitados.',
        ]);

        $this->info("Product created: {$product->id}");

        $unitAmount = (int) round($priceReais * 100);

        $this->info(sprintf('Creating price (%s/month)...', $pricing->format($priceReais)));

        $price = Price::create([
            'product' => $product->id,
            'unit_amount' => $unitAmount, // price in cents, derived from config
            'currency' => $currency,
            'recurring' => [
                'interval' => 'month',
            ],
        ]);

        $this->info("Price created: {$price->id}");
        $this->newLine();
        $this->warn('Add this to your .env file:');
        $this->line("STRIPE_PROFESSIONAL_PRICE_ID={$price->id}");
        $this->newLine();
        $this->info('Stripe setup complete!');

        return Command::SUCCESS;
    }
}
