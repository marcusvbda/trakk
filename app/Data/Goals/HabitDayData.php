<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class HabitDayData
{
    public function __construct(
        public string $date,
        public bool $scheduled,
        public bool $done,
        public bool $canToggle,
    ) {}
}
