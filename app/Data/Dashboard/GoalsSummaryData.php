<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class GoalsSummaryData
{
    public function __construct(
        public int $active,
        public int $onPace,
    ) {}
}
