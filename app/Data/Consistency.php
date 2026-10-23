<?php

declare(strict_types=1);

namespace App\Data;

final readonly class Consistency
{
    /**
     * @param  list<list<int|null>>  $weeks  heat levels, Monday first; null for days that have not happened yet
     */
    public function __construct(
        public array $weeks,
        public int $goodDays,
        public int $totalDays,
        public int $goodPercent,
    ) {}
}
