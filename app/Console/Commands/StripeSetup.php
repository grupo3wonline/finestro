<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeSetup extends Command
{
    protected $signature = 'stripe:setup';
    protected $description = 'Create Stripe product and price for the Professional plan';

    public function handle(): int
    {
        $secretKey = config('stripe.secret_key');

        if (!$secretKey) {
            $this->error('STRIPE_SECRET_KEY not configured in .env');
            return Command::FAILURE;
        }

        Stripe::setApiKey($secretKey);

        $this->info('Creating Stripe product...');

        $product = Product::create([
            'name' => 'Finestro Profissional',
            'description' => 'Plano Profissional do Finestro - Gestão financeira completa com recursos ilimitados.',
        ]);

        $this->info("Product created: {$product->id}");

        $this->info('Creating price (R$ 29,90/month)...');

        $price = Price::create([
            'product' => $product->id,
            'unit_amount' => 2990, // R$ 29,90 in cents
            'currency' => 'brl',
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
