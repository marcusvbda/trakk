<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Goal;
use App\Models\Task;
use App\Services\Goals\GoalProgressService;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class SuggestionService
{
    private const int SAME_DAY_LIMIT = 2;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    /**
     * Rule-based: the active goal furthest behind that has overdue tasks. Nothing overdue, no suggestion.
     *
     * @param  Collection<int, Goal>  $goals  active, with `tasks` (and `tasks.completions` for habits) loaded
     */
    public function suggest(Collection $goals, CarbonImmutable $today): ?string
    {
        $goal = $goals
            ->filter(fn (Goal $goal): bool => $this->overdueCount($goal, $today) > 0)
            ->sortBy(fn (Goal $goal): int => $this->progress->pace($goal, $today)->delta)
            ->first();

        if ($goal === null) {
            return null;
        }

        $count = $this->overdueCount($goal, $today);

        return sprintf(
            'knock out the %d overdue %s %s %s. It unblocks your most behind goal and clears %d %s from red.',
            $count,
            $this->labels->goalName($goal),
            $count === 1 ? 'task' : 'tasks',
            $this->when($count, $today),
            $count,
            $count === 1 ? 'item' : 'items',
        );
    }

    private function overdueCount(Goal $goal, CarbonImmutable $today): int
    {
        return $goal->tasks->filter(fn (Task $task): bool => $task->isOverdueOn($today))->count();
    }

    private function when(int $count, CarbonImmutable $today): string
    {
        return $count <= self::SAME_DAY_LIMIT || $today->isWeekend() ? 'today' : 'on Saturday morning';
    }
}
