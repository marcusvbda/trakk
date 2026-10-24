<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\Goals\HabitDayData;
use App\Data\Goals\WeekScoreData;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Weekly score of a habit goal. Weeks run Monday to Sunday and week 1 is the one the goal was created in.
 * Every scheduled weekday of every recurring action is one check; expects `$goal->user`, `$goal->tasks`
 * and `tasks.completions` loaded.
 */
final class HabitWeekService
{
    private const int HISTORY_WEEKS = 12;

    public function weekNumber(Goal $goal, CarbonImmutable $day): int
    {
        $weeksSinceFirst = $this->firstWeekStart($goal)->diffInDays($day->startOfWeek()) / 7;

        return max(0, (int) floor($weeksSinceFirst)) + 1;
    }

    public function currentScore(Goal $goal, CarbonImmutable $today): WeekScoreData
    {
        return $this->score($goal, $today->startOfWeek());
    }

    /**
     * Closed weeks, latest first.
     *
     * @return list<WeekScoreData>
     */
    public function closedWeeks(Goal $goal, CarbonImmutable $today): array
    {
        $weeks = [];
        $weekStart = $today->startOfWeek()->subWeek();
        $firstWeekStart = $this->firstWeekStart($goal);

        while ($weekStart->greaterThanOrEqualTo($firstWeekStart) && count($weeks) < self::HISTORY_WEEKS) {
            $weeks[] = $this->score($goal, $weekStart);
            $weekStart = $weekStart->subWeek();
        }

        return $weeks;
    }

    /**
     * The seven days of the current week for one action.
     *
     * @return list<HabitDayData>
     */
    public function days(Task $task, CarbonImmutable $today): array
    {
        $monday = $today->startOfWeek();
        $doneOn = $task->completions
            ->map(fn (Completion $completion): string => $completion->completed_on->toDateString())
            ->flip();
        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $monday->addDays($offset);
            $days[] = new HabitDayData(
                $day->toDateString(),
                $task->isScheduledOn($day),
                $doneOn->has($day->toDateString()),
                $this->canCheck($task, $day, $today),
            );
        }

        return $days;
    }

    /** A scheduled day of the current week that has already started: past days can be checked retroactively. */
    public function canCheck(Task $task, CarbonImmutable $day, CarbonImmutable $today): bool
    {
        return $task->isScheduledOn($day) && $day->betweenIncluded($today->startOfWeek(), $today);
    }

    private function score(Goal $goal, CarbonImmutable $weekStart): WeekScoreData
    {
        $weekEnd = $weekStart->addDays(6);
        $done = 0;
        $total = 0;

        foreach ($this->actionsOf($goal, $weekEnd) as $task) {
            $total += count($task->days_of_week ?? []);
            $done += $task->completions
                ->filter(fn (Completion $completion): bool => $completion->completed_on->betweenIncluded($weekStart, $weekEnd)
                    && $task->isScheduledOn($completion->completed_on))
                ->count();
        }

        return new WeekScoreData($this->weekNumber($goal, $weekStart), $done, $total);
    }

    /**
     * Recurring actions that already existed by the end of the week.
     *
     * @return Collection<int, Task>
     */
    private function actionsOf(Goal $goal, CarbonImmutable $weekEnd): Collection
    {
        return $goal->tasks->filter(fn (Task $task): bool => $task->isRecurring()
            && $this->localDate($task->created_at, $goal->user->timezone)->lessThanOrEqualTo($weekEnd));
    }

    private function firstWeekStart(Goal $goal): CarbonImmutable
    {
        return $this->localDate($goal->created_at, $goal->user->timezone)->startOfWeek();
    }

    private function localDate(?CarbonInterface $moment, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::parse($moment?->setTimezone($timezone)->toDateString());
    }
}
