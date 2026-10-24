<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\TodayActionData;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Task;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Pure: expects `$task->goal` and `$task->completions` loaded.
 */
final class TodayListBuilder
{
    public function __construct(private readonly Labels $labels) {}

    /**
     * Recurring tasks scheduled for today's weekday or still unchecked from an earlier scheduled day this week
     * (late), recurring tasks checked today, one-off tasks due today, one-off tasks in `doing` and one-off tasks
     * finished today (so a checked row stays and can be unchecked), by time (tasks without one last), then goal
     * priority.
     *
     * @param  Collection<int, Task>  $tasks
     * @return list<TodayActionData>
     */
    public function build(Collection $tasks, CarbonImmutable $today): array
    {
        return $tasks
            ->filter(fn (Task $task): bool => $this->isOnToday($task, $today))
            ->sortBy([
                fn (Task $a, Task $b): int => ($a->scheduled_time ?? '99:99') <=> ($b->scheduled_time ?? '99:99'),
                fn (Task $a, Task $b): int => $a->goal->priority->rank() <=> $b->goal->priority->rank(),
                fn (Task $a, Task $b): int => $a->position <=> $b->position,
            ])
            ->map(fn (Task $task): TodayActionData => new TodayActionData(
                $task->id,
                $task->title,
                $this->labels->goalName($task->goal),
                $task->goal->color,
                $task->points,
                $this->labels->todayTime($task, $today),
                ! $task->isRecurring() && $task->due_date?->equalTo($today) === true,
                $this->isDone($task, $today),
                $this->daysLate($task, $today),
            ))
            ->values()
            ->all();
    }

    private function isOnToday(Task $task, CarbonImmutable $today): bool
    {
        if ($task->isRecurring()) {
            return $task->isScheduledOn($today)
                || $this->isCompletedOn($task, $today)
                || $this->lateSince($task, $today) !== null;
        }

        return $task->due_date?->equalTo($today) === true
            || ($task->status === TaskStatus::Doing && $task->due_date === null)
            || $this->isCompletedOn($task, $today);
    }

    private function daysLate(Task $task, CarbonImmutable $today): int
    {
        $since = $this->lateSince($task, $today);

        return $since === null ? 0 : (int) $since->diffInDays($today);
    }

    /**
     * The oldest scheduled day of this week, before today, that no completion has covered yet. A completion on
     * a day covers every scheduled day up to it, and a task is not late for days before it existed.
     */
    private function lateSince(Task $task, CarbonImmutable $today): ?CarbonImmutable
    {
        if (! $task->isRecurring()) {
            return null;
        }

        $lastDone = $task->completions->map(fn (Completion $completion): CarbonImmutable => $completion->completed_on)->max();
        $from = $today->startOfWeek();

        if ($task->created_at !== null) {
            $created = CarbonImmutable::parse($task->created_at->toDateString());
            $from = $created->greaterThan($from) ? $created : $from;
        }

        for ($day = $from; $day->lessThan($today); $day = $day->addDay()) {
            if ($lastDone !== null && $day->lessThanOrEqualTo($lastDone)) {
                continue;
            }

            if ($task->isScheduledOn($day)) {
                return $day;
            }
        }

        return null;
    }

    private function isDone(Task $task, CarbonImmutable $today): bool
    {
        return $task->isRecurring()
            ? $this->isCompletedOn($task, $today)
            : $task->status === TaskStatus::Done;
    }

    private function isCompletedOn(Task $task, CarbonImmutable $day): bool
    {
        return $task->completions->contains(fn (Completion $completion): bool => $completion->completed_on->equalTo($day));
    }
}
