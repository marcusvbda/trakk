<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PaceStatus;

final readonly class GoalPace
{
    public function __construct(
        public int $progress,
        public int $expected,
        public int $delta,
        public PaceStatus $status,
    ) {}
}
