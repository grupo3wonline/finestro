<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\AdminMetricsData;

interface AdminMetricsServiceInterface
{
    /**
     * Dashboard numbers: users, plans, subscriptions by status, MRR estimate
     * (active Professional subscriptions x regular price) and this month's
     * paid payments.
     */
    public function metrics(): AdminMetricsData;
}
