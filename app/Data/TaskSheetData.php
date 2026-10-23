<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskStatus;

/**
 * Everything the task sheet needs to edit a task, so opening it costs no request.
 */
final readonly class TaskSheetData
{
    /**
     * @param  list<int>|null  $daysOfWeek  ISO weekdays; null for a one-off task
     * @param  list<ChecklistItemData>  $checklist
     */
    public function __construct(
        public int $id,
        public int $goalId,
        public string $title,
        public int $points,
        public ?TaskStatus $status,
        public ?string $dueDate,
        public ?string $scheduledTime,
        public ?array $daysOfWeek,
        public ?string $tag,
        public ?string $notes,
        public array $checklist,
    ) {}
}
