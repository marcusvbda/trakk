<?php

declare(strict_types=1);

namespace App\Data;

final readonly class Streak
{
    public function __construct(
        public int $current,
        public int $record,
    ) {}
}
