<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\Dashboard\PaceData;
use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;

final readonly class GoalOverviewData
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public GoalType $type,
        public Priority $priority,
        public GoalColor $color,
        public string $colorHex,
        public PaceData $pace,
        public GoalFactsData $facts,
        public GoalObjectiveData $objective,
    ) {}
}
