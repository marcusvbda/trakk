<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class WeeklyProgressData
{
    public function __construct(
        public int $weekNumber,
        public int $doneCount,
        public int $totalCount,
        public int $percent,
    ) {}
}
