<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\EmptyHintsData;
use App\Data\Dashboard\NextDeadlineData;
use App\Data\Dashboard\OverdueItemData;
use App\Data\Dashboard\UpcomingItemData;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Pure: the date-driven lists of the dashboard. Expects each goal's `tasks` loaded, each task's `goal` set.
 */
final class AgendaBuilder
{
    private const int UPCOMING_DAYS = 7;

    public function __construct(private readonly Labels $labels) {}

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<OverdueItemData>
     */
    public function overdue(Collection $tasks, CarbonImmutable $today): array
    {
        return $tasks
            ->filter(fn (Task $task): bool => $task->isOverdueOn($today))
            ->sortBy(fn (Task $task): int => $task->due_date->getTimestamp())
            ->map(fn (Task $task): OverdueItemData => new OverdueItemData(
                $task->id,
                $task->title,
                $this->labels->goalName($task->goal),
                $task->goal->color,
                $this->labels->shortDate($task->due_date),
                (int) $task->due_date->diffInDays($today),
            ))
            ->values()
            ->all();
    }

    /**
     * Open one-off tasks due in the next 7 days plus the goal deadlines that fall in the window.
     *
     * @param  Collection<int, Goal>  $goals
     * @param  Collection<int, Task>  $tasks
     * @return list<UpcomingItemData>
     */
    public function upcoming(Collection $goals, Collection $tasks, CarbonImmutable $today): array
    {
        $last = $today->addDays(self::UPCOMING_DAYS);

        $taskItems = $tasks
            ->filter(fn (Task $task): bool => $this->isOpenOneOff($task) && $task->due_date?->betweenIncluded($today->addDay(), $last) === true)
            ->map(fn (Task $task): array => [
                $task->due_date,
                new UpcomingItemData('task-'.$task->id, $this->labels->dayLabel($task->due_date), $task->title, $this->labels->goalName($task->goal), $task->goal->color),
            ]);

        $goalItems = $goals
            ->filter(fn (Goal $goal): bool => $goal->due_date->betweenIncluded($today->addDay(), $last))
            ->map(fn (Goal $goal): array => [
                $goal->due_date,
                new UpcomingItemData('goal-'.$goal->id, $this->labels->dayLabel($goal->due_date), $goal->title, 'goal deadline', $goal->color),
            ]);

        return $taskItems->concat($goalItems)
            ->sortBy(fn (array $item): int => $item[0]->getTimestamp())
            ->map(fn (array $item): UpcomingItemData => $item[1])
            ->values()
            ->all();
    }

    /**
     * Facts for the empty states: the next day something is scheduled and the next deadline.
     *
     * @param  Collection<int, Goal>  $goals
     * @param  Collection<int, Task>  $tasks
     */
    public function hints(Collection $goals, Collection $tasks, CarbonImmutable $today): EmptyHintsData
    {
        return new EmptyHintsData($this->nextActionDay($tasks, $today), $this->nextDeadline($goals, $tasks, $today));
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function nextActionDay(Collection $tasks, CarbonImmutable $today): ?string
    {
        for ($offset = 1; $offset <= self::UPCOMING_DAYS; $offset++) {
            $day = $today->addDays($offset);

            $isBusy = $tasks->contains(fn (Task $task): bool => $task->isScheduledOn($day)
                || ($this->isOpenOneOff($task) && $task->due_date?->equalTo($day) === true));

            if ($isBusy) {
                return 'next: '.strtolower($this->labels->dayLabel($day));
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Goal>  $goals
     * @param  Collection<int, Task>  $tasks
     */
    private function nextDeadline(Collection $goals, Collection $tasks, CarbonImmutable $today): ?NextDeadlineData
    {
        $deadlines = $tasks
            ->filter(fn (Task $task): bool => $this->isOpenOneOff($task) && $task->due_date?->greaterThanOrEqualTo($today) === true)
            ->map(fn (Task $task): array => [$task->due_date, $task->title])
            ->concat($goals
                ->filter(fn (Goal $goal): bool => $goal->due_date->greaterThanOrEqualTo($today))
                ->map(fn (Goal $goal): array => [$goal->due_date, $goal->title]));

        $next = $deadlines->sortBy(fn (array $deadline): int => $deadline[0]->getTimestamp())->first();

        return $next === null ? null : new NextDeadlineData($next[1], $this->labels->shortDate($next[0]));
    }

    private function isOpenOneOff(Task $task): bool
    {
        return ! $task->isRecurring() && $task->status !== TaskStatus::Done;
    }
}
