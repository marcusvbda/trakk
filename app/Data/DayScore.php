<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PointsTier;
use Carbon\CarbonImmutable;

final readonly class DayScore
{
    private const int GOOD_DAY_PERCENT = 70;

    public function __construct(
        public CarbonImmutable $date,
        public int $points,
        public int $target,
    ) {}

    public function goodDayThreshold(): int
    {
        return intdiv($this->target * self::GOOD_DAY_PERCENT + 99, 100);
    }

    public function isGoodDay(): bool
    {
        return $this->points >= $this->goodDayThreshold();
    }

    public function isTargetHit(): bool
    {
        return $this->points >= $this->target;
    }

    public function tier(): PointsTier
    {
        return match (true) {
            $this->isTargetHit() => PointsTier::Target,
            $this->isGoodDay() => PointsTier::Good,
            default => PointsTier::Low,
        };
    }

    public function percentOfTarget(): int
    {
        return (int) round($this->points / $this->target * 100);
    }

    /** 0 no points, 1 under half the target, 2 half or more, 3 good day, 4 target hit. */
    public function heatLevel(): int
    {
        return match (true) {
            $this->isTargetHit() => 4,
            $this->isGoodDay() => 3,
            $this->points * 2 >= $this->target => 2,
            $this->points > 0 => 1,
            default => 0,
        };
    }
}
