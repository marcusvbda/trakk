<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Data\ChecklistItemData;
use App\Data\TaskSheetData;
use App\Models\ChecklistItem;
use App\Models\Task;
use App\Support\Labels;

/**
 * Expects `$task->checklistItems` loaded.
 */
final class TaskSheetBuilder
{
    public function __construct(private readonly Labels $labels) {}

    public function build(Task $task): TaskSheetData
    {
        return new TaskSheetData(
            $task->id,
            $task->goal_id,
            $task->title,
            $task->points,
            $task->isRecurring() ? null : $task->status,
            $task->due_date?->toDateString(),
            $task->scheduled_time === null ? null : $this->labels->time($task->scheduled_time),
            $task->days_of_week,
            $task->tag,
            $task->notes,
            $task->checklistItems
                ->sortBy('position')
                ->map(fn (ChecklistItem $item): ChecklistItemData => new ChecklistItemData($item->id, $item->title, $item->is_done))
                ->values()
                ->all(),
        );
    }
}
