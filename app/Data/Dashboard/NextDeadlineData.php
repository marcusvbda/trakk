<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class NextDeadlineData
{
    public function __construct(
        public string $title,
        public string $dateLabel,
    ) {}
}
