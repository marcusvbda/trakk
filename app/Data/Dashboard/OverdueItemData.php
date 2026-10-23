<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\GoalColor;

final readonly class OverdueItemData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $goalName,
        public GoalColor $goalColor,
        public string $dueLabel,
        public int $daysLate,
    ) {}
}
