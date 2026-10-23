<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;
use Carbon\CarbonImmutable;

final readonly class GoalData
{
    public function __construct(
        public string $title,
        public GoalType $type,
        public GoalColor $color,
        public Priority $priority,
        public CarbonImmutable $startDate,
        public CarbonImmutable $dueDate,
        public ?int $targetPerWeek = null,
        public ?string $area = null,
        public ?string $description = null,
        public ?string $doneCriteria = null,
        public ?string $icon = null,
        public ?int $relatedGoalId = null,
        public ?string $slug = null,
    ) {}
}
