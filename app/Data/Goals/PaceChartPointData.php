<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class PaceChartPointData
{
    public function __construct(
        public float $day,
        public int $percent,
    ) {}
}
