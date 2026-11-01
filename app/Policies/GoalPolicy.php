<?php

declare(strict_types=1);

namespace App\Policies;

class GoalPolicy extends OwnedResourcePolicy
{
    protected string $permissionPrefix = 'goals';
}
