<?php

declare(strict_types=1);

namespace App\Data;

final readonly class OnTimeRate
{
    public function __construct(
        public int $delivered,
        public int $total,
        public ?int $percent,
    ) {}
}
