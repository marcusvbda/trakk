<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class WeekScoreData
{
    public function __construct(
        public int $number,
        public int $doneCount,
        public int $totalCount,
    ) {}

    public function percent(): int
    {
        return $this->totalCount === 0 ? 0 : (int) round($this->doneCount / $this->totalCount * 100);
    }
}
