<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class PointsChartData
{
    /**
     * @param  list<PointsDayData>  $days  oldest first, ending today
     */
    public function __construct(
        public int $target,
        public int $scaleMax,
        public array $days,
        public string $startLabel,
        public string $middleLabel,
        public float $average,
        public int $bestDay,
        public int $daysAtTarget,
    ) {}
}
