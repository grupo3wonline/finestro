<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Interfaces\Services\StripeConfigCheckInterface;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Shown only when production runs with a Stripe test key. The key itself is
 * never rendered.
 */
class StripeConfigWarning extends Widget
{
    protected static ?int $sort = -4;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.admin.widgets.stripe-config-warning';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('admin.access')
            && app(StripeConfigCheckInterface::class)->isTestKeyInProduction();
    }
}
