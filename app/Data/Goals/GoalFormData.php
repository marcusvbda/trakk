<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Priority;

/**
 * The raw values the goal sheet edits, as opposed to the labels the page shows.
 */
final readonly class GoalFormData
{
    public function __construct(
        public string $slug,
        public string $title,
        public GoalType $type,
        public GoalColor $color,
        public Priority $priority,
        public string $startDate,
        public string $dueDate,
        public ?int $targetPerWeek,
        public ?string $area,
        public ?int $relatedGoalId,
        public ?string $description,
        public ?string $doneCriteria,
        public GoalStatus $status,
        public bool $hasTasks,
    ) {}
}
