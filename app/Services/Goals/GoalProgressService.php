<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\GoalPace;
use App\Data\WeekDots;
use App\Enums\DayState;
use App\Enums\PaceStatus;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Pure calculator: expects `$goal->tasks` loaded, and `tasks.completions` for habits and for `progressOn()`.
 */
final class GoalProgressService
{
    private const int BEHIND_BELOW = -5;

    private const int ON_PACE_BELOW = 5;

    public function progress(Goal $goal): int
    {
        return $this->progressOn($goal, null);
    }

    /** Progress counting only what was done up to and including `$day`; `null` counts everything. */
    public function progressOn(Goal $goal, ?CarbonImmutable $day): int
    {
        return $goal->isHabit() ? $this->habitProgress($goal, $day) : $this->projectProgress($goal, $day);
    }

    public function expected(Goal $goal, CarbonImmutable $today): int
    {
        return (int) round($this->elapsedDays($goal, $today) / $this->totalDays($goal) * 100);
    }

    public function pace(Goal $goal, CarbonImmutable $today): GoalPace
    {
        $progress = $this->progress($goal);
        $expected = $this->expected($goal, $today);
        $delta = $progress - $expected;

        return new GoalPace($progress, $expected, $delta, $this->statusFor($delta));
    }

    /** Progress at the due date if the pace so far holds, capped at 100. */
    public function projection(Goal $goal, CarbonImmutable $today): int
    {
        $progress = $this->progress($goal);
        $elapsed = $this->elapsedDays($goal, $today);

        if ($elapsed === 0) {
            return $progress;
        }

        $remaining = $this->totalDays($goal) - $elapsed;

        return min(100, (int) round($progress + $progress / $elapsed * $remaining));
    }

    /** Tasks (projects) or hit days (habits) done per week since the start. */
    public function currentPace(Goal $goal, CarbonImmutable $today): float
    {
        $elapsed = $this->elapsedDays($goal, $today);

        if ($elapsed === 0) {
            return 0.0;
        }

        return round($this->doneUnitCount($goal) / ($elapsed / 7), 1);
    }

    /** Tasks (projects) or hit days (habits) per week still needed to finish by the due date. Counts at least one remaining day. */
    public function requiredPace(Goal $goal, CarbonImmutable $today): float
    {
        $remainingDays = max(1, $this->totalDays($goal) - $this->elapsedDays($goal, $today));
        $remainingUnits = max(0, $this->totalUnitCount($goal) - $this->doneUnitCount($goal));

        return round($remainingUnits / ($remainingDays / 7), 1);
    }

    /** What `currentPace` and `requiredPace` count per week. */
    public function paceUnit(Goal $goal): string
    {
        return $goal->isHabit() ? 'days/wk' : 'tasks/wk';
    }

    /** Distinct days with at least one completion between the start and `$day` (the due date when `null`). */
    public function hitDayCount(Goal $goal, ?CarbonImmutable $day = null): int
    {
        $until = $day === null ? $goal->due_date : $day->min($goal->due_date);

        return $this->completions($goal)
            ->filter(fn (Completion $completion): bool => $completion->completed_on->betweenIncluded($goal->start_date, $until))
            ->map(fn (Completion $completion): string => $completion->completed_on->toDateString())
            ->unique()
            ->count();
    }

    /** Hit days the habit plans over its whole window: the weekly target times the weeks in the window. */
    public function plannedDayCount(Goal $goal): int
    {
        return (int) round($this->plannedDays($goal));
    }

    /** Monday to Sunday of the current week: which days had at least one completion. */
    public function week(Goal $goal, CarbonImmutable $today): WeekDots
    {
        $hitDays = $this->completions($goal)
            ->map(fn (Completion $completion): string => $completion->completed_on->toDateString())
            ->flip();
        $monday = $today->startOfWeek();
        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $monday->addDays($offset);
            $days[] = match (true) {
                $hitDays->has($day->toDateString()) => DayState::Done,
                $day->greaterThanOrEqualTo($today) => DayState::Upcoming,
                default => DayState::Missed,
            };
        }

        $doneCount = count(array_filter($days, fn (DayState $state): bool => $state === DayState::Done));

        return new WeekDots($days, $doneCount, $goal->target_per_week ?? 0);
    }

    private function projectProgress(Goal $goal, ?CarbonImmutable $day): int
    {
        $total = $goal->tasks->count();

        return $total === 0 ? 0 : (int) round($this->doneTaskCount($goal, $day) / $total * 100);
    }

    private function habitProgress(Goal $goal, ?CarbonImmutable $day): int
    {
        $plannedDays = $this->plannedDays($goal);

        if ($plannedDays <= 0) {
            return 0;
        }

        return min(100, (int) round($this->hitDayCount($goal, $day) / $plannedDays * 100));
    }

    private function plannedDays(Goal $goal): float
    {
        return ($goal->target_per_week ?? 0) * $this->totalDays($goal) / 7;
    }

    private function doneUnitCount(Goal $goal): int
    {
        return $goal->isHabit() ? $this->hitDayCount($goal) : $this->doneTaskCount($goal);
    }

    private function totalUnitCount(Goal $goal): int
    {
        return $goal->isHabit() ? $this->plannedDayCount($goal) : $goal->tasks->count();
    }

    private function doneTaskCount(Goal $goal, ?CarbonImmutable $day = null): int
    {
        return $goal->tasks
            ->filter(fn (Task $task): bool => $task->status === TaskStatus::Done && $this->wasDoneBy($task, $day))
            ->count();
    }

    private function wasDoneBy(Task $task, ?CarbonImmutable $day): bool
    {
        if ($day === null) {
            return true;
        }

        $doneOn = $task->completions->min('completed_on') ?? $task->completed_at?->startOfDay();

        return $doneOn !== null && $doneOn->lessThanOrEqualTo($day);
    }

    /**
     * @return Collection<int, Completion>
     */
    private function completions(Goal $goal): Collection
    {
        return $goal->tasks->flatMap(fn (Task $task) => $task->completions)->values();
    }

    public function totalDays(Goal $goal): int
    {
        return max(1, (int) $goal->start_date->diffInDays($goal->due_date));
    }

    public function elapsedDays(Goal $goal, CarbonImmutable $today): int
    {
        return max(0, min($this->totalDays($goal), (int) $goal->start_date->diffInDays($today, false)));
    }

    private function statusFor(int $delta): PaceStatus
    {
        return match (true) {
            $delta <= self::BEHIND_BELOW => PaceStatus::Behind,
            $delta < 0 => PaceStatus::Watch,
            $delta < self::ON_PACE_BELOW => PaceStatus::OnPace,
            default => PaceStatus::Ahead,
        };
    }
}
