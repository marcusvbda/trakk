<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\WeekDots;

final readonly class GoalProgressData
{
    /**
     * @param  string  $unit  what `doneCount` and `totalCount` count: `tasks` for projects, `days` (hit days) for habits
     * @param  array<string, int>|null  $counts  tasks per status for projects; null for habits
     */
    public function __construct(
        public int $percent,
        public int $expectedPercent,
        public int $doneCount,
        public int $totalCount,
        public string $unit,
        public ?array $counts,
        public ?WeekDots $week,
    ) {}
}
