<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class GoalFactsData
{
    public function __construct(
        public string $start,
        public string $dueDate,
        public int $remainingDays,
        public ?string $frequency,
        public int $overdueCount,
    ) {}
}
