<?php

declare(strict_types=1);

namespace App\Policies;

class BudgetPolicy extends OwnedResourcePolicy
{
    protected string $permissionPrefix = 'budgets';
}
