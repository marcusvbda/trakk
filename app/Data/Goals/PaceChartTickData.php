<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class PaceChartTickData
{
    public function __construct(
        public float $day,
        public string $label,
    ) {}
}
