<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Data\DayScore;
use App\Models\Goal;
use App\Models\Task;
use Carbon\CarbonImmutable;

/**
 * Completion rows for the habit actions, shaped so the dashboard reads like the prototype:
 * a 12 day streak that ended yesterday, a 21 day record before it, and every habit goal near its
 * prototype progress.
 */
final class DemoHistory
{
    private const int RECENT_DAYS = 35;

    private const int BREAK_DAY = 13;

    private const int LOW_DAY_POINTS = 5;

    /**
     * @param  list<Task>  $tasks  recurring actions
     * @param  array<int, Goal>  $goals  habit goals by id
     * @param  array<int, int>  $targetPercent  progress to reach per habit goal id
     * @param  list<int|null>  $skipRotation  habit goal ids a good day leaves out, null for none
     * @param  list<string>  $doneToday  titles of the actions already checked today
     * @return list<array<string, mixed>>
     */
    public function rows(array $tasks, array $goals, array $targetPercent, array $skipRotation, array $doneToday, CarbonImmutable $today, int $userId, int $dailyTarget): array
    {
        $threshold = (new DayScore($today, 0, $dailyTarget))->goodDayThreshold();
        $rows = [];
        $recentHitDays = array_fill_keys(array_keys($goals), 0);

        for ($daysAgo = 0; $daysAgo < self::RECENT_DAYS; $daysAgo++) {
            $day = $today->subDays($daysAgo);
            $scheduled = $this->scheduledOn($tasks, $goals, $day);
            $chosen = $daysAgo === 0
                ? array_values(array_filter($scheduled, fn (Task $task): bool => in_array($task->title, $doneToday, true)))
                : $this->recentChoice($daysAgo, $scheduled, $skipRotation, $threshold);

            foreach ($chosen as $task) {
                $rows[] = $this->row($task, $day, $userId);
            }

            foreach (array_unique(array_map(fn (Task $task): int => $task->goal_id, $chosen)) as $goalId) {
                $recentHitDays[$goalId]++;
            }
        }

        foreach ($goals as $goalId => $goal) {
            array_push($rows, ...$this->olderRows($tasks, $goal, $targetPercent[$goalId], $recentHitDays[$goalId], $today, $userId));
        }

        return $rows;
    }

    /**
     * @param  list<Task>  $scheduled
     * @param  list<int|null>  $skipRotation
     * @return list<Task>
     */
    private function recentChoice(int $daysAgo, array $scheduled, array $skipRotation, int $threshold): array
    {
        return match (true) {
            $daysAgo === self::BREAK_DAY => $this->lowestPoints($scheduled),
            $daysAgo % 3 === 1 => $scheduled,
            default => $this->withoutOneGoal($scheduled, $skipRotation[$daysAgo % count($skipRotation)], $threshold),
        };
    }

    /**
     * @param  list<Task>  $scheduled
     * @return list<Task>
     */
    private function lowestPoints(array $scheduled): array
    {
        usort($scheduled, fn (Task $a, Task $b): int => [$a->points, $a->id] <=> [$b->points, $b->id]);
        $total = 0;
        $chosen = [];

        foreach ($scheduled as $task) {
            if ($total + $task->points > self::LOW_DAY_POINTS) {
                break;
            }

            $total += $task->points;
            $chosen[] = $task;
        }

        return $chosen;
    }

    /**
     * Leaves a goal out for the day, unless that would make it fall short of a good day.
     *
     * @param  list<Task>  $scheduled
     * @return list<Task>
     */
    private function withoutOneGoal(array $scheduled, ?int $skippedGoalId, int $threshold): array
    {
        $remaining = array_values(array_filter($scheduled, fn (Task $task): bool => $task->goal_id !== $skippedGoalId));
        $points = array_sum(array_map(fn (Task $task): int => $task->points, $remaining));

        return $points >= $threshold ? $remaining : $scheduled;
    }

    /**
     * Before the recent window each goal gets the hit days it still needs, spread evenly, with no hit on
     * the day that ends the record run.
     *
     * @param  list<Task>  $tasks
     * @return list<array<string, mixed>>
     */
    private function olderRows(array $tasks, Goal $goal, int $targetPercent, int $recentHitDays, CarbonImmutable $today, int $userId): array
    {
        $lastDay = $today->subDays(self::RECENT_DAYS + 1);
        $dayCount = (int) $goal->start_date->diffInDays($lastDay) + 1;

        if ($dayCount <= 0) {
            return [];
        }

        $plannedDays = $goal->target_per_week * $goal->start_date->diffInDays($goal->due_date) / 7;
        $needed = max(0, min($dayCount, (int) round($targetPercent / 100 * $plannedDays) - $recentHitDays));
        $goalTasks = array_values(array_filter($tasks, fn (Task $task): bool => $task->goal_id === $goal->id));
        $rows = [];

        for ($index = 0; $index < $dayCount; $index++) {
            if (intdiv(($index + 1) * $needed, $dayCount) === intdiv($index * $needed, $dayCount)) {
                continue;
            }

            $day = $goal->start_date->addDays($index);

            foreach ($goalTasks as $task) {
                if ($task->isScheduledOn($day)) {
                    $rows[] = $this->row($task, $day, $userId);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  list<Task>  $tasks
     * @param  array<int, Goal>  $goals
     * @return list<Task>
     */
    private function scheduledOn(array $tasks, array $goals, CarbonImmutable $day): array
    {
        return array_values(array_filter(
            $tasks,
            fn (Task $task): bool => $goals[$task->goal_id]->start_date->lessThanOrEqualTo($day) && $task->isScheduledOn($day),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Task $task, CarbonImmutable $day, int $userId): array
    {
        return [
            'task_id' => $task->id,
            'user_id' => $userId,
            'completed_on' => $day->toDateString(),
            'points' => $task->points,
            'created_at' => $day,
            'updated_at' => $day,
        ];
    }
}
