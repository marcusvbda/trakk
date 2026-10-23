<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class PaceChartData
{
    /**
     * @param  list<PaceChartTickData>  $ticks
     * @param  list<PaceChartPointData>  $actual
     * @param  string  $paceUnit  `tasks/wk` or `days/wk`
     * @param  string|null  $caption  why the chart has nothing to plot yet
     */
    public function __construct(
        public int $totalDays,
        public int $elapsedDays,
        public string $startLabel,
        public string $dueLabel,
        public array $ticks,
        public array $actual,
        public int $expectedPercent,
        public int $projectedPercent,
        public float $currentPace,
        public float $requiredPace,
        public string $paceUnit,
        public ?string $caption,
    ) {}
}
