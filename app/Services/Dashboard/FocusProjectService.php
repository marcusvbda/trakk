<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\FocusProjectData;
use App\Data\Dashboard\KanbanCardData;
use App\Data\Dashboard\KanbanColumnData;
use App\Enums\GoalType;
use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Goals\GoalProgressService;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use stdClass;

final class FocusProjectService
{
    private const int CARDS_PER_COLUMN = 3;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    /**
     * The most behind active project (lowest delta, then earliest due date) as a compact board.
     *
     * @param  Collection<int, Goal>  $goals  active, with `tasks` loaded
     */
    public function for(Collection $goals, CarbonImmutable $today): ?FocusProjectData
    {
        $goal = $goals
            ->filter(fn (Goal $goal): bool => $goal->type === GoalType::Project)
            ->sortBy([
                fn (Goal $a, Goal $b): int => $this->progress->pace($a, $today)->delta <=> $this->progress->pace($b, $today)->delta,
                fn (Goal $a, Goal $b): int => $a->due_date->getTimestamp() <=> $b->due_date->getTimestamp(),
            ])
            ->first();

        if ($goal === null) {
            return null;
        }

        $checklists = $this->checklistCounts($goal->tasks);
        $progress = $this->progress->progress($goal);

        return new FocusProjectData(
            $goal->slug,
            $this->labels->goalName($goal),
            $goal->color,
            $goal->tasks->where('status', TaskStatus::Done)->count(),
            $goal->tasks->count(),
            $progress,
            array_map(
                fn (TaskStatus $status): KanbanColumnData => $this->column($goal->tasks, $status, $checklists, $today),
                TaskStatus::cases(),
            ),
        );
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  Collection<int, stdClass>  $checklists  per task: `total` and `done` checklist items
     */
    private function column(Collection $tasks, TaskStatus $status, Collection $checklists, CarbonImmutable $today): KanbanColumnData
    {
        $inColumn = $tasks->where('status', $status)->sortBy('position')->values();

        return new KanbanColumnData(
            $status,
            max(0, $inColumn->count() - self::CARDS_PER_COLUMN),
            $inColumn
                ->take(self::CARDS_PER_COLUMN)
                ->map(fn (Task $task): KanbanCardData => new KanbanCardData(
                    $task->id,
                    $task->title,
                    $task->due_date === null ? null : $this->labels->shortDate($task->due_date),
                    (int) ($checklists->get($task->id)->done ?? 0),
                    (int) ($checklists->get($task->id)->total ?? 0),
                    $task->isOverdueOn($today),
                    $task->tag,
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, stdClass>
     */
    private function checklistCounts(Collection $tasks): Collection
    {
        return ChecklistItem::query()
            ->whereIn('task_id', $tasks->pluck('id'))
            ->selectRaw('task_id, count(*) as total, count(*) filter (where is_done) as done')
            ->groupBy('task_id')
            ->toBase()
            ->get()
            ->keyBy('task_id');
    }
}
