<?php

declare(strict_types=1);

namespace App\Data;

final readonly class WeekScore
{
    public function __construct(
        public int $points,
        public int $previousPoints,
        public ?int $changePercent,
    ) {}
}
