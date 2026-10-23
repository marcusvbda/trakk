<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\FocusProjectData;
use App\Enums\GoalType;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Services\Goals\GoalProgressService;
use App\Services\Tasks\KanbanBuilder;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FocusProjectService
{
    private const int CARDS_PER_COLUMN = 3;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly KanbanBuilder $kanban,
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

        $goal->tasks->load('checklistItems');

        return new FocusProjectData(
            $goal->slug,
            $this->labels->goalName($goal),
            $goal->color,
            $goal->tasks->where('status', TaskStatus::Done)->count(),
            $goal->tasks->count(),
            $this->progress->progress($goal),
            $this->kanban->columns($goal->tasks, $today, self::CARDS_PER_COLUMN),
        );
    }
}
