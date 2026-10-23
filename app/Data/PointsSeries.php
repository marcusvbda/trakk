<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PointsSeries
{
    /**
     * @param  list<DayScore>  $days  oldest first, ending today
     */
    public function __construct(
        public array $days,
        public float $average,
        public int $bestDay,
        public int $daysAtTarget,
    ) {}
}
