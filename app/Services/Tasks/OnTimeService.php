<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Data\OnTimeRate;
use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class OnTimeService
{
    private const int WINDOW_DAYS = 30;

    /**
     * One-off tasks due in the last 30 days (today included) that were finished on or before their due date.
     * A task due today that is still open is not late yet, so it is left out.
     *
     * @param  Collection<int, Task>  $tasks
     */
    public function rate(Collection $tasks, CarbonImmutable $today, string $timezone): OnTimeRate
    {
        $windowStart = $today->subDays(self::WINDOW_DAYS - 1);

        $due = $tasks->filter(fn (Task $task): bool => ! $task->isRecurring()
            && $task->due_date !== null
            && $task->due_date->betweenIncluded($windowStart, $today)
            && ! ($task->due_date->equalTo($today) && $task->status !== TaskStatus::Done));

        $delivered = $due->filter(fn (Task $task): bool => $this->wasDeliveredOnTime($task, $timezone))->count();

        return new OnTimeRate($delivered, $due->count(), $due->isEmpty() ? null : (int) round($delivered / $due->count() * 100));
    }

    private function wasDeliveredOnTime(Task $task, string $timezone): bool
    {
        if ($task->status !== TaskStatus::Done || $task->completed_at === null || $task->due_date === null) {
            return false;
        }

        return CarbonImmutable::parse($task->completed_at->setTimezone($timezone)->toDateString())->lessThanOrEqualTo($task->due_date);
    }
}
