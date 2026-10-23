<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Data\Dashboard\KanbanCardData;
use App\Data\Dashboard\KanbanColumnData;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Lays one-off tasks out in the three board columns, ordered by position.
 */
final class KanbanBuilder
{
    public function __construct(
        private readonly TaskSheetBuilder $sheets,
        private readonly Labels $labels,
    ) {}

    /**
     * @param  Collection<int, Task>  $tasks  with `checklistItems` loaded
     * @param  int|null  $limit  cards shown per column; the rest is counted in `hiddenCount`
     * @return list<KanbanColumnData>
     */
    public function columns(Collection $tasks, CarbonImmutable $today, ?int $limit = null): array
    {
        return array_map(
            fn (TaskStatus $status): KanbanColumnData => $this->column($tasks, $status, $today, $limit),
            TaskStatus::cases(),
        );
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function column(Collection $tasks, TaskStatus $status, CarbonImmutable $today, ?int $limit): KanbanColumnData
    {
        $inColumn = $tasks->where('status', $status)->sortBy('position')->values();
        $shown = $limit === null ? $inColumn : $inColumn->take($limit);

        return new KanbanColumnData(
            $status,
            $inColumn->count() - $shown->count(),
            $shown->map(fn (Task $task): KanbanCardData => $this->card($task, $today))->values()->all(),
        );
    }

    private function card(Task $task, CarbonImmutable $today): KanbanCardData
    {
        return new KanbanCardData(
            $task->id,
            $task->title,
            $task->due_date === null ? null : $this->labels->shortDate($task->due_date),
            $task->checklistItems->where('is_done', true)->count(),
            $task->checklistItems->count(),
            $task->isOverdueOn($today),
            $task->tag,
            $this->sheets->build($task),
        );
    }
}
