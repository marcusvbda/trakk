<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DayState;

final readonly class WeekDots
{
    /**
     * @param  list<DayState>  $days  Monday to Sunday
     */
    public function __construct(
        public array $days,
        public int $doneCount,
        public int $targetCount,
    ) {}
}
