<?php

declare(strict_types=1);

namespace App\Policies;

class CategoryPolicy extends OwnedResourcePolicy
{
    protected string $permissionPrefix = 'categories';
}
