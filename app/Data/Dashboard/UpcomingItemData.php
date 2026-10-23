<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\GoalColor;

final readonly class UpcomingItemData
{
    public function __construct(
        public string $id,
        public string $dayLabel,
        public string $title,
        public string $goalName,
        public ?GoalColor $goalColor,
    ) {}
}
