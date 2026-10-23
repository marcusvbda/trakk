<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class GoalLinkData
{
    public function __construct(
        public string $title,
        public string $slug,
    ) {}
}
