<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;

final readonly class GoalOptionData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public GoalType $type,
        public GoalColor $color,
        public GoalStatus $status,
    ) {}
}
