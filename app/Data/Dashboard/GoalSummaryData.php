<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Data\WeekDots;
use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;

final readonly class GoalSummaryData
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public GoalType $type,
        public Priority $priority,
        public GoalColor $color,
        public string $startLabel,
        public string $dueLabel,
        public int $daysLeft,
        public string $frequencyLabel,
        public int $percent,
        public int $expectedPercent,
        public PaceData $pace,
        public ?WeekDots $week,
        public ?string $progressLabel,
        public bool $hasBoard,
    ) {}
}
